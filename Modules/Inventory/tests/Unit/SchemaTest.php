<?php

use Illuminate\Support\Facades\Schema;

it('has category_items table', function () {
    expect(Schema::hasTable('category_items'))->toBeTrue();
});
