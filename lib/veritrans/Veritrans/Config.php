<?php

class Veritrans_Config {

  public static $serverKey;
  public static $apiVersion = 2;
  public static $isProduction = false;
  public static $is3ds = false;
  public static $isSanitized = false;

  const SANDBOX_BASE_URL = 'https://api.sandbox.midtrans.com/v2';
  const PRODUCTION_BASE_URL = 'https://api.midtrans.com/v2';
  const SNAP_SANDBOX_BASE_URL = 'https://app.sandbox.midtrans.com/snap/v1';
  const SNAP_PRODUCTION_BASE_URL = 'https://app.midtrans.com/snap/v1';

  public static function getBaseUrl()
  {
    return Veritrans_Config::$isProduction ?
        Veritrans_Config::PRODUCTION_BASE_URL : Veritrans_Config::SANDBOX_BASE_URL;
  }
}
