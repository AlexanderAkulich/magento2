<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Magento
 * @package     Magento_Eav
 * @copyright   Copyright (c) 2013 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * EAV Entity Form Model
 *
 * @category    Magento
 * @package     Magento_Eav
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Eav\Model;

abstract class Form
{
    /**
     * Current module path name
     *
     * @var string
     */
    protected $_moduleName = '';

    /**
     * Current EAV entity type code
     *
     * @var string
     */
    protected $_entityTypeCode = '';

    /**
     * Current store instance
     *
     * @var \Magento\Core\Model\Store
     */
    protected $_store;

    /**
     * Current entity type instance
     *
     * @var \Magento\Eav\Model\Entity\Type
     */
    protected $_entityType;

    /**
     * Current entity instance
     *
     * @var \Magento\Core\Model\AbstractModel
     */
    protected $_entity;

    /**
     * Current form code
     *
     * @var string
     */
    protected $_formCode;

    /**
     * Array of form attributes
     *
     * @var array
     */
    protected $_attributes;

    /**
     * Array of form system attributes
     *
     * @var array
     */
    protected $_systemAttributes;

    /**
     * Array of form user defined attributes
     *
     * @var array
     */
    protected $_userAttributes;

    /**
     * Array of form attributes that is not omitted
     *
     * @var array
     */
    protected $_allowedAttributes = null;

    /**
     * Is AJAX request flag
     *
     * @var boolean
     */
    protected $_isAjax          = false;

    /**
     * Whether the invisible form fields need to be filtered/ignored
     *
     * @var bool
     */
    protected $_ignoreInvisible = true;

    /**
     * @var \Magento\Validator
     */
    protected $_validator = null;

    /**
     * @var \Magento\Core\Model\StoreManager
     */
    protected $_storeManager;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $_eavConfig;

    /**
     * @var \Magento\Core\Model\Config\Modules\Reader
     */
    protected $_modulesReader;

    /**
     * @var \Magento\Eav\Model\AttributeDataFactory
     */
    protected $_attrDataFactory;

    /**
     * @var \Magento\Validator\UniversalFactory $universalFactory
     */
    protected $_universalFactory;

    /**
     * @var \Magento\App\RequestInterface
     */
    protected $_httpRequest;

    /**
     * @var \Magento\Validator\ConfigFactory
     */
    protected $_validatorConfigFactory;

    /**
     * @param \Magento\Core\Model\StoreManager $storeManager
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Core\Model\Config\Modules\Reader $modulesReader
     * @param \Magento\Eav\Model\AttributeDataFactory $attrDataFactory
     * @param \Magento\Validator\UniversalFactory $universalFactory
     * @param \Magento\App\RequestInterface $httpRequest
     * @param \Magento\Validator\ConfigFactory $validatorConfigFactory
     *
     * @throws \Magento\Core\Exception
     */
    public function __construct(
        \Magento\Core\Model\StoreManager $storeManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Core\Model\Config\Modules\Reader $modulesReader,
        \Magento\Eav\Model\AttributeDataFactory $attrDataFactory,
        \Magento\Validator\UniversalFactory $universalFactory,
        \Magento\App\RequestInterface $httpRequest,
        \Magento\Validator\ConfigFactory $validatorConfigFactory
    ) {
        if (empty($this->_moduleName)) {
            throw new \Magento\Core\Exception(__('Current module pathname is undefined'));
        }
        if (empty($this->_entityTypeCode)) {
            throw new \Magento\Core\Exception(__('Current module EAV entity is undefined'));
        }
        $this->_storeManager = $storeManager;
        $this->_eavConfig = $eavConfig;
        $this->_modulesReader = $modulesReader;
        $this->_attrDataFactory = $attrDataFactory;
        $this->_universalFactory = $universalFactory;
        $this->_httpRequest = $httpRequest;
        $this->_validatorConfigFactory = $validatorConfigFactory;
    }

    /**
     * Get EAV Entity Form Attribute Collection
     *
     * @return mixed
     */
    protected function _getFormAttributeCollection()
    {
        return $this->_universalFactory->create(
            str_replace('_', '\\', $this->_moduleName) . '\\Model\\Resource\\Form\\Attribute\\Collection'
        );
    }

    /**
     * Get EAV Entity Form Attribute Collection with applied filters
     *
     * @return \Magento\Eav\Model\Resource\Form\Attribute\Collection|mixed
     */
    protected function _getFilteredFormAttributeCollection()
    {
        return $this->_getFormAttributeCollection()
            ->setStore($this->getStore())
            ->setEntityType($this->getEntityType())
            ->addFormCodeFilter($this->getFormCode())
            ->setSortOrder();
    }

    /**
     * Set current store
     *
     * @param \Magento\Core\Model\Store|string|int $store
     * @return \Magento\Eav\Model\Form
     */
    public function setStore($store)
    {
        $this->_store = $this->_storeManager->getStore($store);
        return $this;
    }

    /**
     * Set entity instance
     *
     * @param \Magento\Core\Model\AbstractModel $entity
     * @return \Magento\Eav\Model\Form
     */
    public function setEntity(\Magento\Core\Model\AbstractModel $entity)
    {
        $this->_entity = $entity;
        if ($entity->getEntityTypeId()) {
            $this->setEntityType($entity->getEntityTypeId());
        }
        return $this;
    }

    /**
     * Set entity type instance
     *
     * @param \Magento\Eav\Model\Entity\Type|string|int $entityType
     * @return \Magento\Eav\Model\Form
     */
    public function setEntityType($entityType)
    {
        $this->_entityType = $this->_eavConfig->getEntityType($entityType);
        return $this;
    }

    /**
     * Set form code
     *
     * @param string $formCode
     * @return \Magento\Eav\Model\Form
     */
    public function setFormCode($formCode)
    {
        $this->_formCode = $formCode;
        return $this;
    }

    /**
     * Return current store instance
     *
     * @return \Magento\Core\Model\Store
     */
    public function getStore()
    {
        if (is_null($this->_store)) {
            $this->_store = $this->_storeManager->getStore();
        }
        return $this->_store;
    }

    /**
     * Return current form code
     *
     * @throws \Magento\Core\Exception
     * @return string
     */
    public function getFormCode()
    {
        if (empty($this->_formCode)) {
            throw new \Magento\Core\Exception(__('Form code is not defined'));
        }
        return $this->_formCode;
    }

    /**
     * Return entity type instance
     * Return EAV entity type if entity type is not defined
     *
     * @return \Magento\Eav\Model\Entity\Type
     */
    public function getEntityType()
    {
        if (is_null($this->_entityType)) {
            $this->setEntityType($this->_entityTypeCode);
        }
        return $this->_entityType;
    }

    /**
     * Return current entity instance
     *
     * @throws \Magento\Core\Exception
     * @return \Magento\Core\Model\AbstractModel
     */
    public function getEntity()
    {
        if (is_null($this->_entity)) {
            throw new \Magento\Core\Exception(__('Entity instance is not defined'));
        }
        return $this->_entity;
    }

    /**
     * Return array of form attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        if (is_null($this->_attributes)) {
            $this->_attributes      = array();
            $this->_userAttributes  = array();
            /** @var $attribute \Magento\Eav\Model\Attribute */
            foreach ($this->_getFilteredFormAttributeCollection() as $attribute) {
                $this->_attributes[$attribute->getAttributeCode()] = $attribute;
                if ($attribute->getIsUserDefined()) {
                    $this->_userAttributes[$attribute->getAttributeCode()] = $attribute;
                } else {
                    $this->_systemAttributes[$attribute->getAttributeCode()] = $attribute;
                }
                if (!$this->_isAttributeOmitted($attribute)) {
                    $this->_allowedAttributes[$attribute->getAttributeCode()] = $attribute;
                }
            }
        }
        return $this->_attributes;
    }

    /**
     * Return attribute instance by code or false
     *
     * @param string $attributeCode
     * @return \Magento\Eav\Model\Entity\Attribute|bool
     */
    public function getAttribute($attributeCode)
    {
        $attributes = $this->getAttributes();
        if (isset($attributes[$attributeCode])) {
            return $attributes[$attributeCode];
        }
        return false;
    }

    /**
     * Return array of form user defined attributes
     *
     * @return array
     */
    public function getUserAttributes()
    {
        if (is_null($this->_userAttributes)) {
            // load attributes
            $this->getAttributes();
        }
        return $this->_userAttributes;
    }

    /**
     * Return array of form system attributes
     *
     * @return array
     */
    public function getSystemAttributes()
    {
        if (is_null($this->_systemAttributes)) {
            // load attributes
            $this->getAttributes();
        }
        return $this->_systemAttributes;
    }

    /**
     * Get not omitted attributes
     *
     * @return array
     */
    public function getAllowedAttributes()
    {
        if (is_null($this->_allowedAttributes)) {
            // load attributes
            $this->getAttributes();
        }
        return $this->_allowedAttributes;
    }

    /**
     * Return attribute data model by attribute
     *
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @return \Magento\Eav\Model\Attribute\Data\AbstractData
     */
    protected function _getAttributeDataModel(\Magento\Eav\Model\Entity\Attribute $attribute)
    {
        $dataModel = $this->_attrDataFactory->create($attribute, $this->getEntity());
        $dataModel->setIsAjaxRequest($this->getIsAjaxRequest());

        return $dataModel;
    }

    /**
     * Prepare request with data and returns it
     *
     * @param array $data
     * @return \Magento\App\RequestInterface
     */
    public function prepareRequest(array $data)
    {
        $request = clone $this->_httpRequest;
        $request->setParamSources();
        $request->clearParams();
        $request->setParams($data);

        return $request;
    }

    /**
     * Extract data from request and return associative data array
     *
     * @param \Magento\App\RequestInterface $request
     * @param string $scope the request scope
     * @param boolean $scopeOnly search value only in scope or search value in global too
     * @return array
     */
    public function extractData(\Magento\App\RequestInterface $request, $scope = null, $scopeOnly = true)
    {
        $data = array();
        /** @var $attribute \Magento\Eav\Model\Attribute */
        foreach ($this->getAllowedAttributes() as $attribute) {
            $dataModel = $this->_getAttributeDataModel($attribute);
            $dataModel->setRequestScope($scope);
            $dataModel->setRequestScopeOnly($scopeOnly);
            $data[$attribute->getAttributeCode()] = $dataModel->extractValue($request);
        }
        return $data;
    }

    /**
     * Get validator
     *
     * @param array $data
     * @return \Magento\Validator
     */
    protected function _getValidator(array $data)
    {
        if (is_null($this->_validator)) {
            $configFiles = $this->_modulesReader->getConfigurationFiles('validation.xml');
            /** @var $validatorFactory \Magento\Validator\Config */
            $validatorFactory = $this->_validatorConfigFactory->create(array('configFiles' => $configFiles));
            $builder = $validatorFactory->createValidatorBuilder('eav_entity', 'form');

            $builder->addConfiguration('eav_data_validator', array(
                'method' => 'setAttributes',
                'arguments' => array($this->getAllowedAttributes())
            ));
            $builder->addConfiguration('eav_data_validator', array(
                'method' => 'setData',
                'arguments' => array($data)
            ));
            $this->_validator = $builder->createValidator();
        }
        return $this->_validator;
    }

    /**
     * Validate data array and return true or array of errors
     *
     * @param array $data
     * @return boolean|array
     */
    public function validateData(array $data)
    {
        $validator = $this->_getValidator($data);
        if (!$validator->isValid($this->getEntity())) {
            $messages = array();
            foreach ($validator->getMessages() as $errorMessages) {
                $messages = array_merge($messages, (array)$errorMessages);
            }
            return $messages;
        }
        return true;
    }

    /**
     * Compact data array to current entity
     *
     * @param array $data
     * @return \Magento\Eav\Model\Form
     */
    public function compactData(array $data)
    {
        /** @var $attribute \Magento\Eav\Model\Attribute */
        foreach ($this->getAllowedAttributes() as $attribute) {
            $dataModel = $this->_getAttributeDataModel($attribute);
            $dataModel->setExtractedData($data);
            if (!isset($data[$attribute->getAttributeCode()])) {
                $data[$attribute->getAttributeCode()] = false;
            }
            $dataModel->compactValue($data[$attribute->getAttributeCode()]);
        }

        return $this;
    }

    /**
     * Restore data array from SESSION to current entity
     *
     * @param array $data
     * @return \Magento\Eav\Model\Form
     */
    public function restoreData(array $data)
    {
        /** @var $attribute \Magento\Eav\Model\Attribute */
        foreach ($this->getAllowedAttributes() as $attribute) {
            $dataModel = $this->_getAttributeDataModel($attribute);
            $dataModel->setExtractedData($data);
            if (!isset($data[$attribute->getAttributeCode()])) {
                $data[$attribute->getAttributeCode()] = false;
            }
            $dataModel->restoreValue($data[$attribute->getAttributeCode()]);
        }
        return $this;
    }

    /**
     * Return array of entity formatted values
     *
     * @param string $format
     * @return array
     */
    public function outputData($format = \Magento\Eav\Model\AttributeDataFactory::OUTPUT_FORMAT_TEXT)
    {
        $data = array();
        /** @var $attribute \Magento\Eav\Model\Attribute */
        foreach ($this->getAllowedAttributes() as $attribute) {
            $dataModel = $this->_getAttributeDataModel($attribute);
            $dataModel->setExtractedData($data);
            $data[$attribute->getAttributeCode()] = $dataModel->outputValue($format);
        }
        return $data;
    }

    /**
     * Restore entity original data
     *
     * @return \Magento\Eav\Model\Form
     */
    public function resetEntityData()
    {
        /** @var $attribute \Magento\Eav\Model\Attribute */
        foreach ($this->getAllowedAttributes() as $attribute) {
            $value = $this->getEntity()->getOrigData($attribute->getAttributeCode());
            $this->getEntity()->setData($attribute->getAttributeCode(), $value);
        }
        return $this;
    }

    /**
     * Set is AJAX Request flag
     *
     * @param boolean $flag
     * @return \Magento\Eav\Model\Form
     */
    public function setIsAjaxRequest($flag = true)
    {
        $this->_isAjax = (bool)$flag;
        return $this;
    }

    /**
     * Return is AJAX Request
     *
     * @return boolean
     */
    public function getIsAjaxRequest()
    {
        return $this->_isAjax;
    }

    /**
     * Set default attribute values for new entity
     *
     * @return \Magento\Eav\Model\Form
     */
    public function initDefaultValues()
    {
        if (!$this->getEntity()->getId()) {
            /** @var $attribute \Magento\Eav\Model\Attribute */
            foreach ($this->getAttributes() as $attribute) {
                $default = $attribute->getDefaultValue();
                if ($default != '') {
                    $this->getEntity()->setData($attribute->getAttributeCode(), $default);
                }
            }
        }
        return $this;
    }

    /**
     * Combined getter/setter whether to omit invisible attributes during rendering/validation
     *
     * @param mixed $setValue
     * @return bool|\Magento\Eav\Model\Form
     */
    public function ignoreInvisible($setValue = null)
    {
        if (null !== $setValue) {
            $this->_ignoreInvisible = (bool)$setValue;
            return $this;
        }
        return $this->_ignoreInvisible;
    }

    /**
     * Whether the specified attribute needs to skip rendering/validation
     *
     * @param \Magento\Eav\Model\Attribute $attribute
     * @return bool
     */
    protected function _isAttributeOmitted($attribute)
    {
        if ($this->_ignoreInvisible && !$attribute->getIsVisible()) {
            return true;
        }
        return false;
    }
}
