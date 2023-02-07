<?php

namespace Insane\Journal\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Core\Transaction;
use Insane\Journal\Models\Invoice\Invoice;

class ReportHelper {
  public function revenueReport($teamId, $methodName = 'payments', $params = null) {
    $year = Carbon::now()->format('Y');
    $previousYear = Carbon::now()->subYear(1)->format('Y');

    $types = [
      'payments' => 'getPaymentsByYear',
      'expenses' => 'getExpensesByYear',
    ];

    $method = $types[$methodName];

    $results = self::$method($year, $teamId, $params);
    $previousYearResult = self::$method($previousYear, $teamId, $params);

    $results = [
        "currentYear" => [
            "year" => $year,
            "values" => $this->mapInMonths($results->toArray(), $year),
            "total" =>  $results->sum('total')
        ],
        "previousYear"=> [
            "year" => $previousYear,
            "values" => $this->mapInMonths($previousYearResult->toArray(), $previousYear),
            "total" =>  $previousYearResult->sum('total')
        ]
    ];
    return $results;
  }

  public static function generateExpensesByPeriod($teamId, $timeUnit = 'month', $timeUnitDiff = 2 , $type = 'expenses') {
    $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    $startDate = Carbon::now()->subMonth($timeUnitDiff)->startOfMonth()->format('Y-m-d');

    $results = self::getExpensesByPeriod($teamId, $startDate, $endDate);
    $resultGroup = $results->groupBy('date');

    return $resultGroup->map(function ($monthItems) {
      return [
        'date' => $monthItems->first()->date,
        'data' => $monthItems->sortByDesc('total')->values()->all(),
        'total' => $monthItems->sum('total')
      ];
    }, $resultGroup);
  }

  public static function getPaymentsByYear($year, $teamId, $payableType = null) {
    $payments = DB::table('payments')
    ->where(DB::raw('YEAR(payments.payment_date)'), '=', $year)
    ->where('team_id', '=', $teamId)
    ->selectRaw('sum(COALESCE(amount,0)) as total, YEAR(payments.payment_date) as year, MONTH(payments.payment_date) as months')
    ->groupByRaw('MONTH(payments.payment_date), YEAR(payments.payment_date)');

    if ($payableType) {
      $payments->where('payments.payable_type', $payableType);
    }

    return $payments->get();
  }

  public static function getExpensesByYear($year, $teamId) {
    return DB::table('transactions')
    ->where(DB::raw('YEAR(transactions.date)'), '=', $year)
    ->where([
        'team_id' => $teamId,
        'direction' => Transaction::DIRECTION_CREDIT,
        'status' => 'verified'
    ])
    ->whereNotNull('category_id')
    ->selectRaw('sum(COALESCE(total,0)) as total, YEAR(transactions.date) as year, MONTH(transactions.date) as months')
    ->groupByRaw('MONTH(transactions.date), YEAR(transactions.date)')
    ->get();
  }

  public static function getExpensesByPeriod($teamId, $startDate, $endDate) {
    return DB::table('transactions')
    ->whereBetween('transactions.date', [$startDate, $endDate])
    ->where([
        'transactions.team_id' => $teamId,
        'direction' => Transaction::DIRECTION_CREDIT,
        'transactions.status' => 'verified'
    ])
    ->whereNotNull('category_id')
    ->selectRaw('sum(COALESCE(total, 0)) as total, date_format(transactions.date, "%Y-%m-01") as date, categories.name, categories.id')
    ->groupByRaw('date_format(transactions.date, "%Y-%m"), categories.id')
    ->join('categories', 'transactions.category_id', '=', 'categories.id')
    ->get();
  }

  public static function getTransactionsByAccount(int $teamId, array $accounts, $startDate = null, $endDate = null, $groupBy = "display_id") {
    $endDate = $endDate ?? Carbon::now()->endOfMonth()->format('Y-m-d');
    $startDate = $startDate ?? Carbon::now()->startOfMonth()->format('Y-m-d');

    $results = DB::table('transaction_lines')
    ->whereBetween('transaction_lines.date', [$startDate, $endDate])
    ->where([
        'transaction_lines.team_id' => $teamId,
        'transactions.status' => 'verified'
    ])
    ->where(function($query) use ($accounts) {
      $query
      ->whereIn('accounts.display_id', $accounts)
      ->orWhereIn('categories.display_id', $accounts)
      ->orWhereIn('g.display_id', $accounts);
    })
    ->selectRaw('
      sum(COALESCE(amount * transaction_lines.type, 0)) as total,
      SUM(CASE
          WHEN transaction_lines.type > 0 THEN amount
          ELSE 0
      END) as income,
      SUM(CASE
        WHEN transaction_lines.type < 0 THEN amount
        ELSE 0
      END) outcome,
      date_format(transactions.date, "%Y-%m-01") as date,
      categories.name,
      categories.id,
      categories.display_id,
      g.display_id groupName'
    )->groupByRaw('date_format(transactions.date, "%Y-%m"), categories.id')
    ->join('accounts', 'accounts.id', '=', 'transaction_lines.account_id')
    ->join('categories', 'accounts.category_id', '=', 'categories.id')
    ->join('transactions', 'transactions.id', '=', 'transaction_id')
    ->join(DB::raw('categories g'), 'g.id', 'categories.parent_id')
    ->get();

    return $results->groupBy($groupBy);


  }

  public static function getAccountTransactionsByPeriod(int $teamId, array $accounts, $startDate = null, $endDate = null, $groupBy = "display_id") {
    $endDate = $endDate ?? Carbon::now()->endOfMonth()->format('Y-m-d');
    $startDate = $startDate ?? Carbon::now()->startOfMonth()->format('Y-m-d');

    $resultGroup = self::getTransactionsByAccount($teamId, $accounts, $startDate, $endDate);

    return array_map(function ($account) use ($resultGroup) {
      return $resultGroup[$account][0] ?? [
        "display_id" => $account,
        "total" => 0
      ];
    }, $accounts);
  }

  public static function getChartTransactionsByPeriod(int $teamId, array $accounts, $startDate = null, $endDate = null) {
    $endDate = $endDate ?? Carbon::now()->endOfMonth()->format('Y-m-d');
    $startDate = $startDate ?? Carbon::now()->startOfMonth()->format('Y-m-d');

    $results = self::getTransactionsByAccount($teamId, $accounts, $startDate, $endDate, "groupName");

    return collect($accounts)->reduce(function ($groups, $account) use ($results) {
      $groups[$account] = isset($results[$account]) ? $results[$account][0] : [];
      return $groups;
    });
  }

  public function getAccountBalance($accountId) {
    return DB::table('transaction_lines')
    ->where([
        'account_id' => $accountId
    ])
    ->selectRaw('sum(amount * type)  as total')
    ->get();
  }

  public function mapInMonths($data, $year) {
    $months = [1, 2, 3,4,5,6,7,8,9,10,11, 12];
    return array_map(function ($month) use ($data, $year) {
        $index = array_search($month, array_column($data, 'months'));

        return  $index !== false ? $data[$index] : [
            "year" => $year,
            "months" => $month,
            "total" =>  0
        ];
    }, $months);
  }

  public function smallBoxRevenue($accountName = 'cash_on_hand', $teamId) {
    $account = Account::where([
        'display_id' => $accountName,
        'team_id' => $teamId
    ])->limit(1)->get();

    $account = count($account) ? $account[0] : null;
    if ($account) {
        $results = $this->getAccountBalance($account->id, $teamId);

        return [
            'accountData' => $account->toArray(),
            'balance' => count($results) ? $results[0]->total : 0
        ];

    } else {
        return [
            'accountData' => [],
            'balance' => 0
        ];
    }
  }

  public function nextInvoices($teamId) {
   return DB::table('invoices')
    ->selectRaw('clients.names contact, clients.id contact_id, invoices.debt, invoices.due_date, invoices.id id, invoices.concept')
    ->where('invoices.team_id', '=', $teamId)
    ->where('invoices.status', '=', 'unpaid')
    ->whereRaw('invoices.due_date >= NOW()')
    ->where('invoices.type', '=', 'INVOICE')

    ->join('clients', 'clients.id', '=', 'invoices.client_id')
    ->groupBy(['clients.names', 'clients.id', 'invoices.debt', 'invoices.due_date', 'invoices.id', 'invoices.concept'])
    ->take(5)
    ->get();
  }

  public function debtors($teamId) {
      return Invoice::byTeam($teamId)
      ->select(DB::raw('count(invoices.id) total_debts, sum(invoices.debt) debt, clients.names contact, clients.id contact_id'))
      ->late()
      ->join('clients', 'clients.id', '=', 'invoices.client_id')
      ->take(5)
      ->groupBy('invoices.client_id', 'clients.names', 'clients.id')
      ->get();
  }

  public static function getReportCategories(string $reportName) {

    $categoriesGroups = [
      "income" => ["income"],
      "expense" => ["expenses"],
      "tax" => ["liabilities"],
      "cash-flow" => ["assets", "liabilities", "equity"],
      "balance-sheet" => ["assets", "liabilities", "equity"],
      "income-statement" => ["income", "expenses"],
      "account-balance" => ["assets", "liabilities", "income", "expenses", "equity"],
    ];

    return $categoriesGroups[$reportName];
  }

  public static function getGeneralLedger($teamId, $reportName, $config = []) {
    $categories = self::getReportCategories($reportName);
    $categoryData = Category::whereIn('display_id', $categories)->get();

    $categoryIds = $categoryData->pluck('id')->toArray();


    $accountQuery = DB::table('categories')
    ->whereIn('categories.parent_id', $categoryIds)
    ->selectRaw('group_concat(accounts.id) as account_ids, group_concat(accounts.name) as account_names')
    ->joinSub(DB::table('accounts')->where('team_id', $teamId), 'accounts','category_id', '=', 'categories.id')
    ->get()
    ->pluck('account_ids');


    if (isset($config['account_id'])) {
      $accountQuery->whereIn('accounts.id', [$config['account_id']]);
    }

    $accountIds = $accountQuery->toArray();


    $accountIds = explode(",", $accountIds[0]);

    $balanceByAccounts = DB::table('transaction_lines')
    ->whereIn('transaction_lines.account_id', $accountIds)
    ->selectRaw("
        SUM(amount * transaction_lines.type) as total,
        SUM(CASE
          WHEN transaction_lines.type > 0 THEN amount
          ELSE 0
        END) as income,
        SUM(CASE
          WHEN transaction_lines.type < 0 THEN amount
          ELSE 0
        END) outcome,
        group_concat(date) dates,
        transaction_lines.account_id,
        accounts.id,
        accounts.name,
        accounts.display_id,
        categories.display_id category,
        gl.display_id ledger,
        gl.id ledger_id
    ")
    ->join('accounts', 'accounts.id', '=', 'transaction_lines.account_id')
    ->join('categories', 'accounts.category_id', 'categories.id')
    ->join(DB::raw('categories gl'), 'categories.parent_id', 'gl.id')
    ->groupBy('transaction_lines.account_id')
    ->orderBy(DB::raw("gl.index, categories.index"));


    if (isset($config['dates'])) {
      $balanceByAccounts = $balanceByAccounts->whereBetween('transaction_lines.date', $config['dates']);
    }

    $accountsWithActivity = $config['account_id'] ? [$config['account_id']] : $balanceByAccounts->pluck('id')->toArray();

    // @todo Analyze this, since I think I just will display subcategories with account transactions I just need to group the first query and the last.
    $categoryAccounts = Category::where([
      'depth' => 1,
      ])
      ->whereIn('parent_id', $categoryIds)
      ->hasAccounts($accountsWithActivity)
      ->with(['accounts' => function ($query) use ($teamId, $accountsWithActivity) {
          $query->where('team_id', '=', $teamId);
          $query->whereIn('id', $accountsWithActivity);
      },
        'category'
      ])
      ->get()
      ->toArray();

    $balance = $balanceByAccounts->get()->toArray();

    $categoryAccounts = array_map(function ($subCategory) use ($balance) {
        $total = [];
        if (isset($subCategory['accounts'])) {
            foreach ($subCategory['accounts'] as $accountIndex => $account) {
                $index = array_search($account['id'], array_column($balance, 'id'));
                if ($index !== false ) {
                    $subCategory['accounts'][$accountIndex]['balance'] = $balance[$index]->total;
                    $subCategory['accounts'][$accountIndex]['income'] = $balance[$index]->income;
                    $subCategory['accounts'][$accountIndex]['outcome'] = $balance[$index]->outcome;
                    $total[] = $balance[$index]->total;
                }
            }
        }
        $subCategory['total'] = array_sum($total);
        return $subCategory;
    }, $categoryAccounts);


    $ledger = DB::table('categories')
    ->whereIn('categories.display_id', $categories)
    ->selectRaw('sum(COALESCE(total, 0)) total, categories.alias, categories.display_id, categories.name')
    ->leftJoinSub($balanceByAccounts, 'balance', function($join) {
        $join->on('categories.id', 'balance.ledger_id');
    })->groupBy('categories.id');

    $ledger = $ledger->get();

    return [
      "ledger" => $ledger,
      "categoryAccounts" => $categoryAccounts
    ];
  }
}
