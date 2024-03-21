<?php

namespace App\Http\Controllers\Debug;

use App\Http\Controllers\Controller;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

class DebugController extends Controller
{
    public function index()
    {
        $number = '+12025550137';

        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $phoneNumberObject = $phoneUtil->parse($number);
            $data = $phoneUtil->getRegionCodeForNumber($phoneNumberObject);
            dd($data);

        } catch (NumberParseException $e) {

            dd($e->getMessage());

        }

    }
}
