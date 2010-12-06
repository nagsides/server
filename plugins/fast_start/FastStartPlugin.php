<?php

class FastStartPlugin extends KalturaPlugin implements IKalturaObjectLoader, IKalturaEnumerator
{
	const PLUGIN_NAME = 'fastStart';
	
	public static function getPluginName()
	{
		return self::PLUGIN_NAME;
	}
	
	/**
	 * @param string $baseClass
	 * @param string $enumValue
	 * @param array $constructorArgs
	 * @return object
	 */
	public static function loadObject($baseClass, $enumValue, array $constructorArgs = null)
	{
		if($baseClass == 'KOperationEngine' && $enumValue == KalturaConversionEngineType::FAST_START)
		{
			if(!isset($constructorArgs['params']) || !isset($constructorArgs['outFilePath']))
				return null;
				
			$params = $constructorArgs['params'];
			return new KOperationEngineFastStart($params->fastStartCmd, $constructorArgs['outFilePath']);
		}
	
		if($baseClass == 'KDLOperatorBase' && $enumValue == FastStartConversionEngineType::get()->apiValue(FastStartConversionEngineType::FAST_START))
		{
			return new KDLOperatorQTFastStart($enumValue);
		}
		
		return null;
	}

	/**
	 * @param string $baseClass
	 * @param string $enumValue
	 * @return string
	 */
	public static function getObjectClass($baseClass, $enumValue)
	{
		if($baseClass == 'KOperationEngine' && $enumValue == FastStartConversionEngineType::get()->apiValue(FastStartConversionEngineType::FAST_START))
			return 'KOperationEngineFastStart';
	
		if($baseClass == 'KDLOperatorBase' && $enumValue == FastStartConversionEngineType::get()->coreValue(FastStartConversionEngineType::FAST_START))
			return 'KDLOperatorQTFastStart';
		
		return null;
	}
	
	/**
	 * @return array<string> list of enum classes names that extend the base enum name
	 */
	public static function getEnums($baseEnumName)
	{
		if($baseEnumName == 'conversionEngineType')
			return array('FastStartConversionEngineType');
			
		return array();
	}
}
