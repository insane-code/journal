<?php

namespace Insane\Journal\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Transaction;
use Insane\Journal\Models\Invoice\Invoice;

class ReportHelper {
  public function revenueReport($teamId, $methodName = 'payments') {
    $year = Carbon::now()->format('Y');
    $previousYear = Carbon::now()->subYear(1)->format('Y');

    $types = [
      'payments' => 'getPaymentsByYear',
      'expenses' => 'getExpensesByYear',
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

  public static function getAccountTransactionsByPeriod(int $teamId, array $accounts, $startDate = null, $endDate = null) {
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
    ->selectRaw('sum(COALESCE(amount * transaction_lines.type, 0)) as total,
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

    $resultGroup = $results->groupBy('display_id');

    return array_map(function ($account) use ($resultGroup) {
      return $resultGroup[$account][0] ?? [
        "display_id" => $account,
        "total" => 0
      ];
    }, $accounts);
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

//   async clientsChange({params, response}) {
//     const table = params.table
//     let dates = [];
//     let interval = 'MONTH';
//     for (let index = 0; index < 12; index++) {
//       if (index == 0) {
//         dates.push(`select DATE_FORMAT(SUBDATE(CURRENT_DATE(),INTERVAL ${index} ${interval}), '%Y-%m-01') as dateUnit`);
//       } else {
//         dates.push(`union all select DATE_FORMAT(SUBDATE(CURRENT_DATE(),INTERVAL ${index} ${interval}), '%Y-%m-01')`);
//       }
//     }

//     const sql = `select
//     dates.dateUnit as unit, count(c.id) as total, DATE_FORMAT(CAST(dates.dateUnit as date), "%M") as month
//     FROM (${dates.join(' ')}) as dates
//     LEFT JOIN ${table} c ON DATE_FORMAT(c.created_at, '%Y-%m-01') = dates.dateUnit
//     GROUP BY dates.dateUnit
//     `

//     const sql2 = `select count(id) as total from ${table}`;

//     // return response.send(sql);
//     const results = await Database.raw(sql);
//     const results2 = await Database.raw(sql2);
//     return response.json({
//       total: results2[0][0].total,
//       values: results[0]
//     });
//   }

//   async cashFlowReport({response}) {
//     const sql = `
//     SELECT
//       SUM(payment_docs.amount) AS total,
//       monthname(MAX(payment_date)) AS month,
//       DATE_FORMAT(payment_docs.payment_date, "%Y%m") AS yearmonth
//     FROM
//       payment_docs
//     GROUP BY
//       DATE_FORMAT(payment_docs.payment_date, "%Y%m")
//     order by
//       DATE_FORMAT(payment_docs.payment_date, "%Y%m") DESC
//     LIMIT
//       12`;

//     const results = await Database.raw(sql);
//     return response.json(results[0]);
//   }

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
}
