<?php

test('the application returns a successful response', function () {
    $response = $this->artisan('inspire');

    $response->assertOk();
});
