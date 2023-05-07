<?php

namespace Insane\Journal\Helpers;

use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Core\Transaction;
use Insane\Journal\Models\Invoice\Invoice;

class ReportHelper {
  public function revenueReport($teamId, $methodName = 'payments') {
    $year = Carbon::now()->format('Y');
    $previousYear = Carbon::now()->subYear(1)->format('Y');

    $types = [
      'payments' => 'getPaymentsByYear',
      'expenses' => 'getExpensesByYear',
      'income' => 'getExpensesByYear',
      'incoming' => 'getIncomingByYear',
      'outgoing' => 'getOutgoingByYear',
    ];

    $method = $types[$methodName];

    $results = self::$method($year, $teamId);
    $previousYearResult = self::$method($previousYear, $teamId);

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

  public static function getPaymentsByYear($year, $teamId) {
    return DB::table('payments')
    ->where(DB::raw('YEAR(payments.payment_date)'), '=', $year)
    ->where('team_id', '=', $teamId)
    ->selectRaw('sum(COALESCE(amount,0)) as total, YEAR(payments.payment_date) as year, MONTH(payments.payment_date) as months')
    ->groupByRaw('MONTH(payments.payment_date), YEAR(payments.payment_date)')
    ->get();
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

  public static function getIncomingByYear($year, $teamId) {
    return DB::table('transaction_lines')
    ->where(DB::raw('YEAR(transactions.date)'), '=', $year)
    ->where([
        'transactions.team_id' => $teamId,
        'type' => 1,
        'transactions.status' => 'verified'
    ])
    ->whereNotNull('category_id')
    ->selectRaw('sum(COALESCE(amount,0)) as total, YEAR(transactions.date) as year, MONTH(transactions.date) as months')
    ->join('transactions', 'transactions.id', '=', 'transaction_lines.transaction.id')
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

  public static function getTransactionsByAccount(int $teamId, array $accounts, $startDate = null, $endDate = null, $groupBy = "display_id", $transactionableType = null) {
    $endDate = $endDate ?? Carbon::now()->endOfMonth()->format('Y-m-d');
    $startDate = $startDate ?? Carbon::now()->startOfMonth()->format('Y-m-d');

    $results = DB::table('transaction_lines')
    ->whereBetween('transactions.date', [$startDate, $endDate])
    ->where([
        'transaction_lines.team_id' => $teamId,
        'transactions.status' => 'verified',
    ])
    ->when($transactionableType, fn ($q) => $q->where('transactionable_type', $transactionableType) )
    ->where(function($query) use ($accounts) {
      $query
      ->whereIn('accounts.display_id', $accounts)
      ->orWhereIn('categories.display_id', $accounts)
      ->orWhereIn('g.display_id', $accounts);
    })
    ->selectRaw('
      sum(COALESCE(amount * transaction_lines.type, 0)) as total,
      SUM(CASE
          WHEN transaction_lines.type = 1 THEN transaction_lines.amount
          ELSE 0
      END) as income,
      SUM(CASE
        WHEN transaction_lines.type = -1 THEN transaction_lines.amount
        ELSE 0
      END) outcome,
      accounts.id account_id,
      group_concat(concat(transaction_lines.amount * transaction_lines.type, ":", transaction_lines.id)) details,
      date_format(transaction_lines.date, "%Y-%m-01") as date,
      accounts.display_id account_display_id,
      accounts.name account_name,
      accounts.alias account_alias,
      categories.name,
      categories.id,
      categories.display_id,
      categories.alias,
      g.display_id groupName,
      g.alias groupAlias,
      MONTH(transactions.date) as months'
    )->groupByRaw('date_format(transactions.date, "%Y-%m"), accounts.id, categories.id')
    ->join('accounts', 'accounts.id', '=', 'transaction_lines.account_id')
    ->join('categories', 'accounts.category_id', '=', 'categories.id')
    ->join('transactions', 'transactions.id', '=', 'transaction_id')
    ->join(DB::raw('categories g'), 'g.id', 'categories.parent_id')
    ->get();

    return $groupBy ? $results->groupBy($groupBy) : $results;
  }

  public static function getAccountTransactionsByMonths(int $teamId, array $accounts, $startDate = null, $endDate = null, $groupBy = null, $transactionableType = null) {
    $endDate = $endDate ?? Carbon::now()->endOfMonth()->format('Y-m-d');
    $startDate = $startDate ?? Carbon::now()->startOfMonth()->format('Y-m-d');

    return DB::table('transaction_lines')
    ->whereBetween('transactions.date', [$startDate, $endDate])
    ->where([
        'transaction_lines.team_id' => $teamId,
        'transactions.status' => 'verified',
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
          WHEN transaction_lines.type = 1 THEN transaction_lines.amount
          ELSE 0
      END) as income,
      SUM(CASE
        WHEN transaction_lines.type = -1 THEN transaction_lines.amount
        ELSE 0
      END) outcome,
      accounts.id account_id,
      group_concat(CASE
        WHEN transaction_lines.type = -1
        THEN concat(transaction_lines.amount * transaction_lines.type, ":", transaction_lines.id, ":" , accounts.display_id, "|paymentId:", transactions.transactionable_id, "|transactionType:", transactions.transactionable_type)
        ELSE ""
        END
      ) outgoing_details,
      group_concat(CASE
        WHEN transaction_lines.type = 1
        THEN concat(transaction_lines.amount * transaction_lines.type, ":", transaction_lines.id, ":" , accounts.display_id)
        ELSE ""
        END
      ) income_details,
      date_format(transaction_lines.date, "%Y-%m-01") as date,
      accounts.display_id account_display_id,
      accounts.name account_name,
      accounts.alias account_alias,
      categories.name,
      categories.id,
      categories.display_id,
      categories.alias,
      g.display_id groupName,
      g.alias groupAlias,
      transactions.transactionable_id,
      MONTH(transactions.date) as months'
    )->when($groupBy, fn ($q) => $q->groupByRaw($groupBy))
    ->join('accounts', 'accounts.id', '=', 'transaction_lines.account_id')
    ->join('categories', 'accounts.category_id', '=', 'categories.id')
    ->join('transactions', function (JoinClause $join) use ($transactionableType) {
      $join->on('transactions.id', '=', 'transaction_id')
           ->when($transactionableType, fn ($q) => $q->where('transactions.transactionable_type', $transactionableType));
    })
    ->join(DB::raw('categories g'), 'g.id', 'categories.parent_id')
    ->orderByRaw('g.index')
    ->get();
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

  public static function getReportConfig(string $reportName) {

    $categoriesGroups = [
      "income" => [
        "categories" =>["income"]
      ],
      "expense" => [
        "categories" =>["expenses"]
      ],
      "tax" => [
        "categories" => ["liabilities"]
      ],
      "cash-flow" => [
        "categories" => ["assets", "liabilities", "equity"]
      ],
      "balance-sheet" => [
        "categories" => ["assets", "liabilities", "equity"]
      ],
      "income-statement" => [
        "categories" => ["income", "expenses"]
      ],
      "account-balance" => [
        "categories" => ["assets", "liabilities", "income", "expenses", "equity"],
      ],
      "payments" => [
        "categories" => ["real_state", "expected_payments_owners", "real_state_operative", "expected_commissions_owners"],
        "type" => Payment::class,
        "groupBy" => "account_display_id"
      ]
    ];

    return $categoriesGroups[$reportName];
  }

  public static function getGeneralLedger($teamId, $reportName, $config = []) {
    $reportConfig = self::getReportConfig($reportName);
    $queryResults = self::getAccountTransactionsByMonths(
      $teamId,
      $reportConfig["categories"],
      $config["dates"][0] ?? null,
      $config["dates"][1] ?? null,
      $reportConfig["groupBy"] ?? "display_id",
      null
    );

    if (isset($config['account_id'])) {
      $queryResults->whereIn('accounts.id', [$config['account_id']]);
    }

    return [
      "ledger" => [
        "ledgers" => $queryResults->groupBy('groupName')->map(fn ($ledger) => [
          "total" => $ledger->sum("total"),
          "outcome" => $ledger->sum("outcome"),
          "income" => $ledger->sum("income"),
          "alias" => $ledger->first()->groupAlias,
          "name" => $ledger->first()->groupName,
          "categories" => $ledger->groupBy("display_id")->map(fn($category) => [
            "total" => $category->sum("total"),
            "outcome" => $category->sum("outcome"),
            "income" => $category->sum("income"),
            "alias" => $category->first()->alias,
            "name" => $category->first()->display_id,
            "accounts" => $category->groupBy('account_display_id')->map(fn ($accounts) => [
              "total" => $accounts->sum("total"),
              "outcome" => $accounts->sum("outcome"),
              "income" => $accounts->sum("income"),
              "alias" => $accounts->first()->account_alias,
              "name" => $accounts->first()->account_name,
              "items" => $accounts->all()
            ])->all(),
          ]),
        ]),
        "total" => $queryResults->sum("total"),
        "outcome" => $queryResults->sum("outcome"),
        "income" => $queryResults->sum("income"),
      ]
    ];
  }
}
