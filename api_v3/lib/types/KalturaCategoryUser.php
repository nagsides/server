<?php
/**
 * @package api
 * @subpackage objects
 */
class KalturaCategoryUser extends KalturaObject implements IFilterable {
	/**
	 * 
	 * @var int
	 * @insertonly
	 * @filter eq,in
	 */
	public $categoryId;
	
	/**
	 * User id
	 * 
	 * @var string
	 * @insertonly
	 * @filter eq,in
	 */
	public $userId;
	
	/**
	 * Partner id
	 * 
	 * @var int
	 * @readonly
	 */
	public $partnerId;
	
	/**
	 * Permission level
	 * 
	 * @var KalturaCategoryUserPermissionLevel
	 * @filter eq,in
	 */
	public $permissionLevel;
	
	/**
	 * Status
	 * 
	 * @var KalturaCategoryUserStatus
	 * @readonly
	 * @filter eq,in
	 */
	public $status;
	
	/**
	 * CategoryUser creation date as Unix timestamp (In seconds)
	 * 
	 * @var int
	 * @readonly
	 * @filter gte,lte,order
	 */
	public $createdAt;
	
	/**
	 * CategoryUser update date as Unix timestamp (In seconds)
	 * 
	 * @var int
	 * @readonly
	 * @filter gte,lte,order
	 */
	public $updatedAt;
	
	/**
	 * Update method can be either manual or automatic to distinguish between manual operations (for example in KMC) on automatic - using bulk upload 
	 * 
	 * @var KalturaUpdateMethodType
	 * @filter eq, in
	 */
	public $updateMethod;
	
	/**
	 * The full ids of the Category
	 * 
	 * @var string
	 * @readonly
	 * @filter likex,eq
	 */
	public $categoryFullIds;
	
	private static $mapBetweenObjects = array
	(
		"categoryId",
		"userId" => "puserId",
		"partnerId",
		"permissionLevel",
		"status",
		"createdAt",
		"updatedAt",
		"updateMethod",
		"categoryFullIds",	
	);
	
	public function toObject($dbObject = null, $skip = array()) {
		if (is_null ( $dbObject ))
			$dbObject = new categoryKuser ();
		
		parent::toObject ( $dbObject, $skip );
		
		return $dbObject;
	}
	
	/*
	 * mapping between the field on this object (on the left) and the setter/getter on the CategoryKuser object (on the right)  
	 */
	public function getMapBetweenObjects() {
		return array_merge ( parent::getMapBetweenObjects (), self::$mapBetweenObjects );
	}
	
	public function getExtraFilters() {
		return array ();
	}
	
	public function getFilterDocs() {
		return array ();
	}
	
	/* (non-PHPdoc)
	 * @see KalturaObject::validateForInsert()
	 */
	public function validateForInsert($propertiesToSkip = array()) 
	{
		$kuser = kuserPeer::getKuserByPartnerAndUid ( kCurrentContext::$partner_id, $this->userId );
		if (! $kuser)
			throw new KalturaAPIException ( KalturaErrors::INVALID_USER_ID, $this->userId );
		
		$category = categoryPeer::retrieveByPK ( $this->categoryId );
		if (! $category)
			throw new KalturaAPIException ( KalturaErrors::CATEGORY_NOT_FOUND, $this->categoryId );
		
		if ($category->getInheritanceType () == InheritanceType::INHERIT)
			throw new KalturaAPIException ( KalturaErrors::CATEGORY_INHERIT_MEMBERS, $this->categoryId );
		
		$categoryKuser = categoryKuserPeer::retrieveByCategoryIdAndKuserId ( $this->categoryId, $kuser->getId () );
		if ($categoryKuser)
			throw new KalturaAPIException ( KalturaErrors::CATEGORY_USER_ALREADY_EXISTS );
		
		$currentKuserCategoryKuser = categoryKuserPeer::retrieveByCategoryIdAndActiveKuserId ( $this->categoryId, kCurrentContext::$ks_kuser_id );
		if ((! $currentKuserCategoryKuser || 
				$currentKuserCategoryKuser->getPermissionLevel () != CategoryKuserPermissionLevel::MANAGER) && 
				$category->getUserJoinPolicy () == UserJoinPolicyType::NOT_ALLOWED && 
				kEntitlementUtils::getEntitlementEnforcement ()) {
			throw new KalturaAPIException ( KalturaErrors::CATEGORY_USER_JOIN_NOT_ALLOWED, $this->categoryId );
		}
		
		return parent::validateForInsert ( $propertiesToSkip );
	}
}