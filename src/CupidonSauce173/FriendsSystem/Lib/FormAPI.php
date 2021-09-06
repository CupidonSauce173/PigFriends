<?php


namespace CupidonSauce173\FriendsSystem\Lib;


class FormAPI
{
    /**
     * @param callable|null $function
     * @return SimpleForm
     */
    public function createSimpleForm(callable $function = null): SimpleForm
    {
        return new SimpleForm($function);
    }
}