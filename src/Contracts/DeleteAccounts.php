<?php

namespace Insane\Journal\Contracts;

interface DeleteAccounts {
    public function validate(mixed $user, mixed $account);
    public function delete(mixed $account);
}
