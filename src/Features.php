<?php

namespace Insane\Journal;

class Features
{
    /**
     * Determine if the given feature is enabled.
     *
     * @param  string  $feature
     * @return bool
     */
    public static function enabled(string $feature)
    {
        return in_array($feature, config('journal.features', []));
    }

    /**
     * Determine if the feature is enabled and has a given option enabled.
     *
     * @param  string  $feature
     * @param  string  $option
     * @return bool
     */
    public static function optionEnabled(string $feature, string $option)
    {
        return static::enabled($feature) &&
               config("journal-options.{$feature}.{$option}") === true;
    }

    /**
     * Determine if the application is using transaction categories features.
     *
     * @return bool
     */
    public static function hasTransactionCategoriesFeature()
    {
        return static::enabled(static::transactionCategories());
    }

    /**
     * Enable the profile photo upload feature.
     *
     * @return string
     */
    public static function transactionCategories()
    {
        return 'transaction-categories';
    }
}
