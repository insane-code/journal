<?php

namespace Insane\Journal;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportHelper
{

  public function revenueReport($teamId) {
    $year = Carbon::now()->format('Y');
    $previousYear = Carbon::now()->subYear(1)->format('Y');

    $results = $this->getPaymentsByYear($year, $teamId);
    $previousYearResult = $this->getPaymentsByYear($previousYear, $teamId);

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

  public function getPaymentsByYear($year, $teamId) {
    return DB::table('payments')
    ->where(DB::raw('YEAR(payments.payment_date)'), '=', $year)
    ->where('team_id', '=', $teamId)
    ->selectRaw('sum(COALESCE(amount,0)) as total, YEAR(payments.payment_date) as year, MONTH(payments.payment_date) as months')
    ->groupByRaw('MONTH(payments.payment_date), YEAR(payments.payment_date)')
    ->get();
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
    ->where('type', '=', 'INVOICE')
    ->join('clients', 'clients.id', '=', 'invoices.client_id')
    ->take(5)
    ->get();
  }

  public function debtors($teamId) {
      return DB::table('invoices')
      ->selectRaw('count(invoices.id) total_debts, sum(invoices.debt) debt, clients.names contact,clients.id contact_id')
      ->where('invoices.team_id', '=', $teamId)
      ->where('invoices.status', '=', 'unpaid')
      ->whereRaw('invoices.due_date <= NOW()')
      ->where('type', '=', 'INVOICE')
      ->join('clients', 'clients.id', '=', 'invoices.client_id')
      ->take(5)
      ->groupBy('invoices.client_id')
      ->get();
    }
}
