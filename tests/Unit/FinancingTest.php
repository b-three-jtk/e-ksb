<?php

test('Menghitung simulasi estimasi cicilan per bulan', function () {
    $costPrice = 1000000;
    $marginAmount = $costPrice * 0.08;
    $downPayment = 200000;

    $totalPrice = $costPrice + $marginAmount - $downPayment;
    $tenor = 10;
    $installmentPerMonth = $totalPrice / $tenor;

    // to int
    $installmentPerMonth = (int) $installmentPerMonth;
    expect($installmentPerMonth)->toBe(88000);
});

test('Menghitung Qimah Haliyyah', function () {
    $costPrice = 1000000;
    $marginAmount = $costPrice * 0.08;
    $tenor = 10;

    $tsamanNaqdy = $costPrice + $marginAmount;
    $costPricePaid = ($costPrice / $tenor) * 5; // sudah bayar 5 bulan
    $remainingCostPrice = $costPrice - $costPricePaid;

    $qimahHaliyyah = $remainingCostPrice + $marginAmount;

    $qimahHaliyyah = (int) $qimahHaliyyah;
    expect($qimahHaliyyah)->toBe(508000);
});

test('Menghitung PU-PMJST', function () {
    $costPrice = 1000000;
    $marginAmount = $costPrice * 0.08;
    $tenor = 10;

    $tsamanNaqdy = $costPrice + $marginAmount;
    $costPricePaid = ($costPrice / $tenor) * 5; // sudah bayar 5 bulan
    $remainingCostPrice = $costPrice - $costPricePaid;

    $qimahHaliyyah = $remainingCostPrice + $marginAmount;

    $qimahHaliyyah = (int) $qimahHaliyyah;
    expect($qimahHaliyyah)->toBe(508000);
});

test('Menghitung Qimah Ismiyyahh', function () {
    $costPrice = 19000000;
    $margin1Bulan = $costPrice * 0.08;
    $tenor = 10;

    $qimahIsmiyyah = $costPrice + ($margin1Bulan * $tenor);

    $qimahIsmiyyah = (int) $qimahIsmiyyah;
    expect($qimahIsmiyyah)->toBe(342);
});
