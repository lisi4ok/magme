<?php
/**
 * @package lisi4ok_Magme
 * @author  Nikola Haralamov <lisi4ok@gmail.com>
 * 
 * @todo    Add Product Custom Options in export.
 */

class Magme
{
	private $_attributesMap = array(
		'store' => 'admin',
		'attribute_set' => 'Default',
		'status' => 'Enabled',
		'visibility' => 4,
		'configurable_attributes' => null,
		'simple_skus' => null,
		'type' => null,
		'sku' => null,
		'categories' => null,
		'name' => null,
		'description' => null,
		'short_description' => null,
		'news_from_date' => null,
		'news_to_date' => null,
		'weight' => null,
		'price' => null,
		'special_price' => null,
		'special_from_date' => null,
		'special_to_date' => null,
		'tax_class_id' => null,
		'image' => null,
		'small_image' => null,
		'thumbnail' => null,
		'media_gallery' => null,
		'image_label' => null,
		'small_image_label' => null,
		'thumbnail_label' => null,
		'manage_stock' => null,
		'qty' => null,
		'is_in_stock' => null,
		/* Default Inventory Cofnig and values
			'use_config_manage_stock' => 1,
			'use_config_min_qty' => 1,
			'use_config_backorders' => 1,
			'use_config_min_sale_qty' => 1,
			'use_config_max_sale_qty' => 1,
			'use_config_notify_stock_qty' => 1,
			'use_config_qty_increments' => 1,
			'use_config_enable_qty_inc' => 1,
			'use_config_enable_qty_increments' => 1,
			'manage_stock' => 0,
			'qty' => 0,
			'is_in_stock' => 0,
			'min_qty' => 0,
			'backorders' => 0,
			'min_sale_qty' => 1,
			'max_sale_qty' => 0,
			'notify_stock_qty' => null,
			'stock_status_changed_auto' => 1,
			'qty_increments' => 0,
			'enable_qty_increments' => 0,
			'stock_status_changed_automatically' => 1,
		 */
		// 'has_options' => null,
		// 'required_options' => null,
	);

	private $_additionalAttributes = array();

	public function __construct()
	{
		require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php';
		error_reporting(E_ALL & ~E_DEPRECATED | E_STRICT);
		set_time_limit(0);
		ini_set('display_errors', '1');
		ini_set('log_errors', '1');
		umask(0);
		Mage::setIsDeveloperMode(true);
		Mage::app();
		Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
	}

	public function getProductCollection($filters = array())
	{
		$select = Mage::getModel('catalog/product')->getCollection()
			->addAttributeToSelect('*')
			->addUrlRewrite()
		;
		if (!empty($filters)) {
			foreach ($filters as $filter => $value) {
				$select->addAttributeToFilter($filter, $value);
			}
		}
		$collection = array();
		foreach ($select as $product) {
			$collection[] = Mage::getModel('catalog/product')->load($product->getId());
		}
		return $collection;
	}

	public function getConfigurableProducts()
	{
		return $this->getProductCollection(array('type_id' => Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE));
	}

	public function getSimpleProducts($ignoreAssociatedOfConfigurable = true)
	{
		$collection = $this->getProductCollection(array('type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE));
		if ($ignoreAssociatedOfConfigurable == true) {
			$collection->addAttributeToFilter('entity_id', array('nin' => $this->getAssociatedOfConfigurableProductIds()));
		}
		return $collection;
	}

	public function getAssociatedOfConfigurableProductIds()
	{
		$associatedOfConfigurableProductids = array();
		foreach ($this->getConfigurableProducts() as $key => $configurableProduct) {
			$associatedProducts = $configurableProduct
				->getTypeInstance(true)
				->getUsedProducts(null, $configurableProduct);
			foreach ($associatedProducts as $key => $associatedProduct) {
				if (!in_array($associatedProduct->getId(), $associatedOfConfigurableProductids)) {
					$associatedOfConfigurableProductids[] = $associatedProduct->getId();
				}
			}
		}
		return $associatedOfConfigurableProductids;
	}

	public function getProductVisibilityLabel($visibility)
	{
		$visibilities = Mage_Catalog_Model_Product_Visibility::getOptionArray();
		if (array_key_exists($visibility, $visibilities)) {
			return $visibilities[$visibility];
		}
		return $visibility;
	}

	public function getProductStatusLabel($status)
	{
		$statuses = Mage_Catalog_Model_Product_Status::getOptionArray();
		if (array_key_exists($status, $statuses)) {
			return $statuses[$status];
		}
		return $status;
	}

	public function getProductAttributeSetLabel($attributeSetId)
	{
		if ($attributeSet = Mage::getModel('eav/entity_attribute_set')->load($attributeSetId)) {
			return $attributeSet->getAttributeSetName();
		}
		return $attributeSetId;
	}

	public function getProductStockLabel($availability)
	{
		$stocks = array(
			Mage_CatalogInventory_Model_Stock::STOCK_IN_STOCK => Mage::helper('cataloginventory')->__('In Stock'),
			Mage_CatalogInventory_Model_Stock::STOCK_OUT_OF_STOCK => Mage::helper('cataloginventory')->__('Out of Stock'),
		);
		if (array_key_exists($availability, $stocks)) {
			return $stocks[$availability];
		}
		return $availability;
	}

	public function getProductCategories($product)
	{
		if (!$product instanceof Mage_Catalog_Model_Product) {
			$product = Mage::getModel('catalog/product')->load($product);
		}
		$collection = $product->getCategoryCollection()
			->addAttributeToFilter('entity_id', array('nin' => array(0,1,2)))
			->addNameToResult();
		$categories = array();
		foreach ($collection as $category) {
			$categories[] = $this->getCategoryNamePathByIdPath($category->getPath());
		}
		return $categories;
	}

	public function getCategoryNamePathByIdPath($idPath) {
		$categories = explode('/', str_replace('1/2/', '', $idPath));
		$namePath = [];
		foreach ($categories as $category) {
			$category = Mage::getModel('catalog/category')->load($category);
			$namePath[] = $category->getName();
		}
		return implode('/', $namePath);
	}

	public function validateImage($image)
	{
		$allowedImagesTypes = array('gif', 'jpeg', 'jpg', 'png', 'bmp');
		$parts = explode('.', basename($image));
		if (array_key_exists(1, $parts)) {
			if (function_exists('mb_strtolower')) {
				return in_array(mb_strtolower($parts[1], 'UTF-8'), $allowedImagesTypes);
			} else {
				return in_array(strtolower($parts[1]), $allowedImagesTypes);
			}
		}
		return false;
	}

	public function setAdditionalAttributes($attributes)
	{
		foreach ($attributes as $attribute) {
			if (array_key_exists($attribute, $this->_attributesMap)) {
				continue;
			}
			$this->_additionalAttributes[] = $attribute;
		}
		return $this;
	}

	public function getAdditionalAttributes()
	{
		return $this->_additionalAttributes;
	}

	public function pascalCase($str)
	{
		return str_replace(' ', '', ucwords(str_replace('_', ' ', $str)));
	}

	public function export()
	{
		$file = fopen('products.csv', 'w');
		$firstLine = $this->_attributesMap;
		$additionalAttributes = $this->getAdditionalAttributes();
		if (!empty($additionalAttributes)) {
			foreach ($additionalAttributes as $attribute) {
				$firstLine[$attribute] = null;
			}
		}
		fputcsv($file, array_keys($firstLine));
		foreach ($this->getProductCollection() as $product) {
			$line = array();
			$configurableAttributes = array();
			$simpleSkus = array();
			$gallery = array();
			$line[] = 'admin';
			$line[] = $this->getProductAttributeSetLabel($product->getAttributeSetId());
			$line[] = $this->getProductStatusLabel($product->getStatus());
			$line[] = $this->getProductVisibilityLabel($product->getVisibility());

			/*
			 * Configurable Products
			 */
			if ($product->isConfigurable()) {
				foreach ($product->getTypeInstance(true)->getConfigurableAttributes($product) as $attribute) {
					$configurableAttributes[] = $attribute->getProductAttribute()->getAttributeCode();
				}
				foreach ($product->getTypeInstance(true)->getUsedProducts(null, $product) as $associatedProduct) {
					$simpleSkus[] = $associatedProduct->getSku();
				}
			}
			if (!empty($simpleSkus) && !empty($configurableAttributes)) {
				$line[] = implode(',', $configurableAttributes);
				$line[] = implode(',', $simpleSkus);
			} else {
				$line[] = '';
				$line[] = '';
			}
			/*
			 * Configurable Products
			 */

			$line[] = $product->getTypeId();
			$line[] = $product->getSku();
			$line[] = implode(';;', $this->getProductCategories($product));
			$line[] = $product->getName();
			$line[] = $product->getDescription();
			$line[] = $product->getShortDescription();
			$line[] = $product->getNewsFromDate();
			$line[] = $product->getNewsToDate();
			$line[] = $product->getWeight();
			$line[] = $product->getPrice();
			$line[] = $product->getSpecialPrice();
			$line[] = $product->getSpecialFromDate();
			$line[] = $product->getSpecialToDate();
			$line[] = $product->getTaxClassId();

			/*
			 * Product Images
			 */
			if ($product->getImage() && $this->validateImage($product->getImage())) {
				$line[] = $product->getImage(); // image
				$line[] = $product->getImage(); // small_image
				$line[] = $product->getImage(); // thumbnail
			} else {
				$line[] = '';
				$line[] = '';
				$line[] = '';
			}
			if ($product->getMediaGalleryImages()) {
				foreach ($product->getMediaGalleryImages() as $image) {
					if ($this->validateImage($image->getFile())) {
						$gallery[$image->getFile()] = $image->getFile();
					}
				}
			}
			if (!empty($gallery)) {
				$line[] = implode(';', $gallery);
			} else {
				$line[] = '';
			}
			$line[] = $product->getImageLabel();
			$line[] = $product->getSmallImageLabel();
			$line[] = $product->getThumbnailLabel();
			/*
			 * Product Images
			 */

			$line[] = (string)$product->getStockItem()->getManageStock();
			$line[] = $product->getStockItem()->getQty();
			$line[] = $product->getStockItem()->getIsInStock();

			if (!empty($additionalAttributes)) {
				foreach ($additionalAttributes as $attribute) {
					$line[] = $product->{'get' . $this->pascalCase($attribute)}();
				}
			}
			fputcsv($file, $line);
		}
		fclose($file);
	}
}