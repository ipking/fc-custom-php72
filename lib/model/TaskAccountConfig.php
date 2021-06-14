<?php
namespace DataCenter\lib\model;
use DataCenter\lib\table\TableTaskAcountConfig;

/**
 * User: Lite Scaffold
 */
class TaskAccountConfig extends TableTaskAcountConfig {
    const PLATFORM_ALLEGRO = 'allegro';
	const PLATFORM_EBAY = 'ebay';
	const PLATFORM_AMAZON = 'amazon';
	const PLATFORM_CDISCOUNT = 'cdiscount';
	const PLATFORM_MANO = 'manomano';
	const PLAT_RAKUTEN = 'rakuten';
	const PLATFORM_MERCADOLIBRE = 'mercadolibre';
	const PLATFORM_REAL = 'real';
	const PLATFORM_AMERICANAS = 'americanas';
	const PLATFORM_LINIO = 'linio';
	const PLATFORM_PAYPAL = 'paypal';

	static $platform_map = array(
	    self::PLATFORM_ALLEGRO => 'allegro',
        self::PLATFORM_EBAY => 'ebay',
        self::PLATFORM_AMAZON => 'amazon',
        self::PLATFORM_CDISCOUNT => 'cdiscount',
        self::PLATFORM_MANO => 'mano',
        self::PLAT_RAKUTEN => 'rakuten',
        self::PLATFORM_MERCADOLIBRE => 'mercadolibre',
        self::PLATFORM_REAL => 'real',
        self::PLATFORM_AMERICANAS => 'americanas',
        self::PLATFORM_LINIO => 'linio',
        self::PLATFORM_PAYPAL => 'paypal',
    );
	const STATUS_ENABLED = '0';
	const STATUS_DISABLED = '1';
	
	public static $status_map = [
		self::STATUS_ENABLED=>'启用',
		self::STATUS_DISABLED=>'停用'
	];
	
	public function __construct($data = array()){
		parent::__construct($data);
	}
	
	
}