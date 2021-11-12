<?php


namespace CupidonSauce173\PigFriends\Lib;


class FormAPI
{
    /**
     * @param callable|null $function
     * @return SimpleForm
     */
    function createSimpleForm(callable $function = null): SimpleForm
    {
        return new SimpleForm($function);
    }

    /**
     * @param callable|null $function
     * @return CustomForm
     */
    public function createCustomForm(?callable $function = null): CustomForm
    {
        return new CustomForm($function);
    }
}