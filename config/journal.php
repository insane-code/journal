<?php

return [
    "accounts_inertia_path" => "Journal/Accounts",
    "transactions_inertia_path" => "Journal/Transactions",
    "products_inertia_path" => "Journal/Catalog/Products",
    "categories_inertia_path" => "Journal/Catalog/Categories",
    "invoices_inertia_path" => "Journal/Invoices",
    "payments_inertia_path" => "Journal/Payments",
    "accounts_categories" => [
        [
            "resource" => "categories",
            "display_id" => "assets",
            "name" => "Assets",
            "Description" => "",
            "depth" => 0,
            "childs" => [
                [
                    "resource" => "categories",
                    "display_id" => "cash_and_bank",
                    "name" => "Cash and bank",
                    "Description" => "Use this to track the balance of cash that is immediately available for use. Examples of this are bank accounts, cash boxes in a register, money boxes, or electronic accounts such as PayPa",
                    "depth" => 1,
                ],
                [
                    "resource" => "categories",
                    "display_id" => "money_in_transit",
                    "name" => "Money in Transit",
                    "Description" => "Use this to track the balance of money that is expected to deposited or withdrawn into or from a Cash and Bank account at a future date, usually within days. Examples of this are credit card sales that have been processed but have not yet been deposited into your bank, or checks (written or received) that have not been deposited into or withdrawn from your bank account yet.",
                    "depth" => 1,
                ],
                [
                    "resource" => "categories",
                    "display_id" => "expected_payments_customers",
                    "name" => "Expected Payments from Customers",
                    "Description" => "Use this to track the balance of what customers owe you after you have made a sale. Invoices in Journal are already tracked in the Accounts Receivable category.",
                    "depth" => 1,
                ],
                [
                    "resource" => "categories",
                    "display_id" => "inventory",
                    "name" => "Inventory",
                    "Description" => "",
                    "depth" => 1,
                ],
            ]
        ],
        [
            "resource" => "categories",
            "name" => "Liabilities & Credit Cards",
            "display_id" => "liabilities",
            "Description" => "",
            "depth" => 0,
            "childs" => [
                [
                    "resource" => "categories",
                    "display_id" => "credit_card",
                    "name" => "Credit Card",
                    "Description" => "Use this to track purchases made using a credit card. Create an account for each credit card you use in your business. Purchases using your credit card, and payments to your credit card, should be recorded in the relevant credit card category.",
                    "depth" => 1,
                ],
                [
                    "resource" => "categories",
                    "display_id" => "loan_line_credit",
                    "name" => "Loan and Line of Credit",
                    "Description" => "Use this to track the balance of outstanding loans or withdrawals you've made using a line of credit. The cash you receive as a result of a loan or line of credit is deposited into a Cash and Bank category.",
                    "depth" => 1,
                ],
                [
                    "resource" => "categories",
                    "display_id" => "expected_payments_vendors",
                    "name" => "Expected Payments to Vendors",
                    "Description" => "Use this to track the balance of what you owe vendors (i.e. suppliers, online subscriptions providers) after you accepted their service or receive items for which you have not yet paid. Journal in Wave are already tracked in the Accounts Payable category",
                    "depth" => 1,
                ],
                [
                    "resource" => "categories",
                    "display_id" => "sales_taxes",
                    "name" => "Sales Taxes",
                    "Description" => "Use this to track the sales taxes you have charged to customers during a sale, and sales tax amounts you have remitted to the government. The balance of this category indicates how much you have to remit to the government. This category can also be used to track sales taxes you been charged on purchases, so that you can reduce how much sales taxes you have to remit to the government. If you create a sales tax in Wave, a category here is created for you automatically.",
                    "depth" => 1,
                ],
                [
                    "resource" => "categories",
                    "display_id" => "due_payroll",
                    "name" => "Due for Payroll",
                    "Description" => "Use this to track all amounts owed that relate to having employees and running a payroll. This includes salaries, wages, and employee reimbursements, but also all payroll taxes that must be paid to government agencies and other collectors (ie; insurance agencies and health savings providers).",

                    "depth" => 1,
                ],
                [
                    "resource" => "categories",
                    "display_id" => "due_to_business",
                    "name" => "Due to You and Others Business Owners",
                    "Description" => "Use this to track the balance of what you (or your partners) have personally loaned to the business, but expect to be paid back for. The same category can also be used to track loans the business has given you (or your partners), in which case the balance would be less than zero (negative).",

                    "depth" => 1,
                ],
                [
                    "resource" => "categories",
                    "display_id" => "customer_prepayments",
                    "name" => "Customer Prepayments and Customer Credits",
                    "Description" => "",
                    "depth" => 1,
                ],
            ]
        ],
        [
            "resource" => "categories",
            "name" => "Incomes",
            "display_id" => "incomes",
            "Description" => "",
            "depth" => 0,
            "childs" => [
                [
                    "resource" => "categories",
                    "display_id" => "income",
                    "name" => "Income",
                    "Description" => "Use this to track all your sales to customers, whether your customer has made a payment or not. These are the categories used when you create an Invoice in Wave. Any sales taxes charged to customers will not be tracked using a Sales category, but will be tracked using a Sales Taxes on Sales or Purchases category.",
                    "depth" => 1,
                ],
                [
                    "resource" => "categories",
                    "display_id" => "discount",
                    "name" => "Discounts",
                    "Description" => "",
                    "depth" => 1,
                ],
            ]
        ],
        [
            "resource" => "categories",
            "name" => "Expense",
            "display_id" => "expenses",
            "Description" => "",
            "depth" => 0,
            "childs" => [
                [
                    "resource" => "categories",
                    "display_id" => "operating_expense",
                    "name" => "Operating Expense",
                    "Description" => "",
                    "depth" => 1,
                ],
                [
                    "resource" => "categories",
                    "display_id" => "cost_goods_sold",
                    "name" => "Cost of Goods Sold",
                    "Description" => "",
                    "depth" => 1,
                ],
                [
                    "resource" => "categories",
                    "display_id" => "payment_processing_fee",
                    "name" => "Payment Processing Fee",
                    "Description" => "",
                    "depth" => 1,
                ],
                [
                    "resource" => "categories",
                    "display_id" => "payroll_expense",
                    "name" => "Payroll Expense",
                    "Description" => "",
                    "depth" => 1,
                ],
                [
                    "resource" => "categories",
                    "display_id" => "uncategorized_expense",
                    "name" => "Uncategorized Expense",
                    "Description" => "",
                    "depth" => 1,
                ],
                [
                    "resource" => "categories",
                    "display_id" => "loss_foreign_exchange",
                    "name" => "Loss on Foreign Exchange",
                    "Description" => "",
                    "depth" => 1,
                ],
            ]
        ]
    ],
    "accounts_catalog" => [
        [
            "category_id" => "cash_and_bank",
            "display_id" => "cash_on_hand",
            "name" => "Cash on Hand",
            "index" => 0,
            "balance_type" => "DEBIT"
        ],
        [
            "category_id" => "cash_and_bank",
            "display_id" => "daily_box",
            "name" => "Daily Box",
            "index" => 1,
            "balance_type" => "DEBIT"
        ],
        [
            "category_id" => "expected_payments_customers",
            "display_id" => "accounts_receibable",
            "name" => "Accounts Recievable",
            "index" => 1,
            "balance_type" => "CREDIT"
        ],
        [
            "category_id" => "inventory",
            "display_id" => "products",
            "name" => "Products",
            "index" => 1,
            "balance_type" => "DEBIT"
        ],
        [
            "category_id" => "equimpment",
            "display_id" => "machines",
            "name" => "Machines",
            "index" => 1,
            "balance_type" => "DEBIT"
        ],
    ]
];
