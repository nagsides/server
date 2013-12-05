<?php

/**
 * @package plugins.scheduledTask
 * @subpackage lib.objectTaskEngine
 */
class KObjectTaskDeleteEntryFlavorsEngine extends KObjectTaskEntryEngineBase
{
	/**
	 * @param KalturaBaseEntry $object
	 */
	function processObject($object)
	{
		/** @var KalturaDeleteEntryFlavorsObjectTask $objectTask */
		$objectTask = $this->getObjectTask();
		$deleteType = $objectTask->deleteType;
		$flavorParams = array_map('trim', explode(',', $objectTask->flavorParams));
		// remove empty elements that can be identified as flavor params 0
		$flavorParams = array_filter($flavorParams, create_function('$a','return strlen($a) > 0;'));
		$client = $this->getClient();

		$pager = new KalturaFilterPager();
		$pager->pageSize = 500; // use max size, throw exception in case we got more than 500 flavors where pagination is not supported
		$filter = new KalturaFlavorAssetFilter();
		$filter->entryIdEqual = $object->id;
		$flavorsResponse = $client->flavorAsset->listAction($filter);
		if ($flavorsResponse->totalCount > $pager->pageSize)
			throw new Exception('Too many flavors were found where pagination is not supported');

		$flavors = $flavorsResponse->objects;
		KalturaLog::debug('Found '.count($flavors). ' flavors');
		if (!count($flavors))
			return;

		KalturaLog::debug('Delete type is '.count($deleteType));
		switch($deleteType)
		{
			case KalturaDeleteFlavorsLogicType::DELETE_LIST:
				$this->deleteFlavorByList($flavors, $flavorParams);
				break;
			case KalturaDeleteFlavorsLogicType::KEEP_LIST_DELETE_OTHERS:
				$this->deleteFlavorsKeepingConfiguredList($flavors, $flavorParams);
				break;
			case KalturaDeleteFlavorsLogicType::DELETE_KEEP_SMALLEST:
				$this->deleteAllButKeepSmallest($flavors);
				break;
		}
	}

	/**
	 * @param $id
	 */
	protected function deleteFlavor($id, $partnerId)
	{
		KalturaLog::debug('Deleting flavor id '.$id);
		$client = $this->getClient();
		try
		{
			$client->flavorAsset->delete($id);
		}
		catch(Exception $ex)
		{
			$this->unimpersonate();
			KalturaLog::err($ex);
			KalturaLog::err('Failed to delete flavor id '.$id);
		}
	}

	protected function findSmallestFlavor($flavors)
	{
		/** @var KalturaFlavorAsset $smallestFlavor */
		$smallestFlavor = null;
		foreach($flavors as $flavor)
		{
			/** @var KalturaFlavorAsset $flavor */
			if ($flavor->status != KalturaFlavorAssetStatus::READY)
				continue;

			if (!$flavor->size) // flavor must have size
				continue;

			if (is_null($smallestFlavor) || $flavor->size < $smallestFlavor->size)
			{
				$smallestFlavor = $flavor;
			}
		}

		return $smallestFlavor;
	}

	/**
	 * @param $flavors
	 * @param $flavorParams
	 */
	protected function deleteFlavorsKeepingConfiguredList(array $flavors, array $flavorParams)
	{
		// make sure at least one flavor will be left from the configured list
		$atLeastOneFlavorWillBeLeft = false;
		foreach ($flavors as $flavor)
		{
			/** @var $flavor KalturaFlavorAsset */
			if ($flavor->status != KalturaFlavorAssetStatus::READY)
				continue;

			if (in_array($flavor->flavorParamsId, $flavorParams))
			{
				$atLeastOneFlavorWillBeLeft = true;
				break;
			}
		}

		if (!$atLeastOneFlavorWillBeLeft)
		{
			KalturaLog::warning('No flavors will be left after deletion, cannot continue.');
			return;
		}

		foreach ($flavors as $flavor)
		{
			/** @var $flavor KalturaFlavorAsset */
			if (!in_array($flavor->flavorParamsId, $flavorParams))
			{
				$this->deleteFlavor($flavor->id, $flavor->partnerId);
			}
		}
	}

	/**
	 * @param $flavors
	 * @param $flavorParams
	 */
	protected function deleteFlavorByList(array $flavors, array $flavorParams)
	{
		foreach ($flavors as $flavor)
		{
			/** @var $flavor KalturaFlavorAsset */
			if (in_array($flavor->flavorParamsId, $flavorParams))
			{
				$this->deleteFlavor($flavor->id, $flavor->partnerId);
			}
		}
	}

	protected function deleteAllButKeepSmallest(array $flavors)
	{
		$smallestFlavor = $this->findSmallestFlavor($flavors);
		if (is_null($smallestFlavor))
		{
			KalturaLog::warning('Smallest flavor was not found, cannot continue');
			return;
		}
		$this->deleteFlavorsKeepingConfiguredList($flavors, array($smallestFlavor->flavorParamsId));
	}
}