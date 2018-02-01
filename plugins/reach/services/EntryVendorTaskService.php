<?php
/**
 * Entry Vendor Task Service
 *
 * @service entryVendorTask
 * @package plugins.reach
 * @subpackage api.services
 * @throws KalturaErrors::SERVICE_FORBIDDEN
 */

class EntryVendorTaskService extends KalturaBaseService
{

	public function initService($serviceId, $serviceName, $actionName)
	{
		parent::initService($serviceId, $serviceName, $actionName);

		if (!ReachPlugin::isAllowedPartner($this->getPartnerId()))
			throw new KalturaAPIException(KalturaErrors::FEATURE_FORBIDDEN, ReachPlugin::PLUGIN_NAME);

		if (!in_array($actionName, array('getJobs')))
			$this->applyPartnerFilterForClass('entryVendorTask');
	}

	/**
	 * Allows you to add a entry vendor task
	 *
	 * @action add
	 * @param KalturaEntryVendorTask $entryVendorTask
	 * @return KalturaEntryVendorTask
	 * @throws KalturaErrors::ENTRY_ID_NOT_FOUND
	 * @throws KalturaReachErrors::VENDOR_PROFILE_NOT_FOUND
	 * @throws KalturaReachErrors::CATALOG_ITEM_NOT_FOUND
	 * @throws KalturaReachErrors::ENTRY_VENDOR_TASK_DUPLICATION
	 * @throws KalturaReachErrors::EXCEEDED_MAX_CREDIT_ALLOWED
	 */
	public function addAction(KalturaEntryVendorTask $entryVendorTask)
	{
		$entryVendorTask->validateForInsert();
		
		$dbEntry = entryPeer::retrieveByPK($entryVendorTask->entryId);
		if(!$dbEntry)
			throw new KalturaAPIException(KalturaErrors::ENTRY_ID_NOT_FOUND, $entryVendorTask->entryId);
		
		$dbVendorProfile = VendorProfilePeer::retrieveByPK($entryVendorTask->vendorProfileId);
		if(!$dbVendorProfile)
			throw new KalturaAPIException(KalturaReachErrors::VENDOR_PROFILE_NOT_FOUND, $entryVendorTask->vendorProfileId);
		
		$dbVendorCatalogItem = VendorCatalogItemPeer::retrieveByPK($entryVendorTask->catalogItemId);
		if(!$dbVendorCatalogItem)
			throw new KalturaAPIException(KalturaReachErrors::CATALOG_ITEM_NOT_FOUND, $entryVendorTask->catalogItemId);
		
		if(EntryVendorTaskPeer::retrieveEntryIdAndCatalogItemId($entryVendorTask->entryId, $entryVendorTask->catalogItemId, kCurrentContext::getCurrentPartnerId()))
			throw new KalturaAPIException(KalturaReachErrors::ENTRY_VENDOR_TASK_DUPLICATION, $entryVendorTask->entryId, $entryVendorTask->catalogItemId, kCurrentContext::getCurrentPartnerId());
		
		if(!kReachUtils::isEnoughCreditLeft($dbEntry, $dbVendorCatalogItem, $dbVendorProfile))
			throw new KalturaAPIException(KalturaReachErrors::EXCEEDED_MAX_CREDIT_ALLOWED);
		
		$dbEntryVendorTask = kReachManager::addEntryVendorTask($dbEntry, $dbVendorProfile, $dbVendorCatalogItem);
		$entryVendorTask->toInsertableObject($dbEntryVendorTask);
		$dbEntryVendorTask->save();

		// return the saved object
		$entryVendorTask->fromObject($dbEntryVendorTask, $this->getResponseProfile());
		return $entryVendorTask;
	}

	/**
	 * Retrieve specific entry vendor task by id
	 *
	 * @action get
	 * @param int $id
	 * @return KalturaEntryVendorTask
	 * @throws KalturaReachErrors::VENDOR_PROFILE_NOT_FOUND
	 */
	function getAction($id)
	{
		$dbEntryVendorTask = EntryVendorTaskPeer::retrieveByPK($id);
		if (!$dbEntryVendorTask)
			throw new KalturaAPIException(KalturaReachErrors::ENTRY_VENDOR_TASK_NOT_FOUND, $id);

		$entryVendorTask = new KalturaEntryVendorTask();
		$entryVendorTask->fromObject($dbEntryVendorTask, $this->getResponseProfile());
		return $entryVendorTask;
	}

	/**
	 * List KalturaEntryVendorTask objects
	 *
	 * @action list
	 * @param KalturaEntryVendorTaskFilter $filter
	 * @param KalturaFilterPager $pager
	 * @return KalturaEntryVendorTaskListResponse
	 */
	public function listAction(KalturaEntryVendorTaskFilter $filter = null, KalturaFilterPager $pager = null)
	{
		if (!$filter)
			$filter = new KalturaEntryVendorTaskFilter();

		if (!$pager)
			$pager = new KalturaFilterPager();

		return $filter->getListResponse($pager, $this->getResponseProfile());
	}

	/**
	 * get KalturaEntryVendorTask objects for specific vendor partner
	 *
	 * @action getJobs
	 * @param KalturaEntryVendorTaskFilter $filter
	 * @param KalturaFilterPager $pager
	 * @return KalturaEntryVendorTaskListResponse
	 */
	public function getJobsAction(KalturaEntryVendorTaskFilter $filter = null, KalturaFilterPager $pager = null)
	{
		if (PermissionPeer::isValidForPartner(PermissionName::REACH_VENDOR_PARTNER_PERMISSION, kCurrentContext::getCurrentPartnerId()))
			throw new KalturaAPIException(KalturaReachErrors::ENTRY_VENDOR_TASK_SERVICE_GET_JOB_NOT_ALLOWED, kCurrentContext::$partner_id);

		if (!$filter)
			$filter = new KalturaEntryVendorTaskFilter();

		$filter->vendorPartnerIdEqual = kCurrentContext::getCurrentPartnerId();
		if (!$pager)
			$pager = new KalturaFilterPager();

		return $filter->getListResponse($pager, $this->getResponseProfile());
	}

	/**
	 * Update entry vendor task. Only the properties that were set will be updated.
	 *
	 * @action update
	 * @param int $id vendor task id to update
	 * @param KalturaEntryVendorTask $entryVendorTask evntry vendor task to update
	 *
	 * @throws KalturaReachErrors::ENTRY_VENDOR_TASK_NOT_FOUND
	 */
	public function updateAction($id, KalturaEntryVendorTask $entryVendorTask)
	{
		$dbEntryVendorTask = entryPeer::retrieveByPK($id);
		if (!$dbEntryVendorTask)
			throw new KalturaAPIException(KalturaErrors::ENTRY_VENDOR_TASK_NOT_FOUND, $id);
		
		$dbEntryVendorTask = $entryVendorTask->toUpdatableObject($dbEntryVendorTask);
		$dbEntryVendorTask->save();

		// return the saved object
		$entryVendorTask = new KalturaEntryVendorTask();
		$entryVendorTask->fromObject($dbEntryVendorTask, $this->getResponseProfile());
		return $entryVendorTask;
	}

	/**
	 * Approve entry vendor task for execution.
	 *
	 * @action approve
	 * @param int $id vendor task id to approve
	 * @param KalturaEntryVendorTask $entryVendorTask evntry vendor task to approve
	 *
	 * @throws KalturaReachErrors::ENTRY_VENDOR_TASK_NOT_FOUND
	 * @throws KalturaReachErrors::CANNOT_APPROVE_NOT_MODERATED_TASK
	 * @throws KalturaReachErrors::EXCEEDED_MAX_CREDIT_ALLOWED
	 */
	public function approveAction($id)
	{
		$dbEntryVendorTask = EntryVendorTaskPeer::retrieveByPK($id);
		if (!$dbEntryVendorTask )
			throw new KalturaAPIException(KalturaReachErrors::ENTRY_VENDOR_TASK_NOT_FOUND, $id);
		
		if($dbEntryVendorTask->getStatus() != EntryVendorTaskStatus::PENDING_MODERATION)
			throw new KalturaAPIException(KalturaReachErrors::CANNOT_APPROVE_NOT_MODERATED_TASK);
		
		if(!kReachUtils::checkCreditForApproval($dbEntryVendorTask))
			throw new KalturaAPIException(KalturaReachErrors::EXCEEDED_MAX_CREDIT_ALLOWED, $dbEntryVendorTask->getEntry(), $dbEntryVendorTask->getCatalogItem());
		
		$dbEntryVendorTask->setStatus(KalturaEntryVendorTaskStatus::PENDING);
		$dbEntryVendorTask->save();
		
		// return the saved object
		$entryVendorTask = new KalturaEntryVendorTask();
		$entryVendorTask->fromObject($dbEntryVendorTask, $this->getResponseProfile());
		return $entryVendorTask;
	}
	
	/**
	 * Reject entry vendor task for execution.
	 *
	 * @action reject
	 * @param int $id vendor task id to reject
	 * @param KalturaEntryVendorTask $entryVendorTask evntry vendor task to reject
	 *
	 * @throws KalturaReachErrors::ENTRY_VENDOR_TASK_NOT_FOUND
	 * @throws KalturaReachErrors::CANNOT_REJECT_NOT_MODERATED_TASK
	 */
	public function rejectAction($id)
	{
		$dbEntryVendorTask = EntryVendorTaskPeer::retrieveByPK($id);
		if (!$dbEntryVendorTask )
			throw new KalturaAPIException(KalturaReachErrors::ENTRY_VENDOR_TASK_NOT_FOUND, $id);
		
		if($dbEntryVendorTask->getStatus() != EntryVendorTaskStatus::PENDING_MODERATION)
			throw new KalturaAPIException(KalturaReachErrors::CANNOT_REJECT_NOT_MODERATED_TASK);
		
		$dbEntryVendorTask->setStatus(KalturaEntryVendorTaskStatus::REJECTED);
		$dbEntryVendorTask->save();
		
		// return the saved object
		$entryVendorTask = new KalturaEntryVendorTask();
		$entryVendorTask->fromObject($dbEntryVendorTask, $this->getResponseProfile());
		return $entryVendorTask;
	}
}