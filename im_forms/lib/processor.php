<?php namespace ImForms;

class Processor
{
	protected $imanager;
	protected $itemMapper;
	public $config;

	/**
	 * @var null - Current form item buffer
	 */
	public $currentItemId = null;

	/**
	 * @var null - Current form object buffer
	 */
	public $currentForm = null;

	/**
	 * @var null - Current field object buffer
	 */
	public $currentField = null;

	/**
	 * Processor constructor.
	 *
	 */
	public function __construct(Config $config)
	{
		$this->imanager = imanager();
		$this->itemMapper = $this->imanager->getItemMapper();
		$this->config = $config;
	}


	/**
	 * Returns IM Template Parser
	 */
	public function getTemplateParser() { return $this->imanager->getTemplateEngine(); }

	/**
	 * Returns IM Sanitizer class
	 * @return mixed
	 */
	public function getSanitizer() { return $this->imanager->sanitizer; }

	/**
	 * Returns IM-SectionCache
	 * @return mixed
	 */
	public function getCache() { return $this->imanager->getSectionCache(); }

	/**
	 * Returns SimpleItem object of the imforms category by form name
	 *
	 * @param $name
	 *
	 * @return null | SimpleItem object
	 */
	public function getSimpleItemByFormName($name)
	{
		$this->itemMapper->alloc($this->config->imFormsCategoryId);
		$simpleItem = $this->itemMapper->getSimpleItem("name=$name");

		if(!$simpleItem) return null;

		return $simpleItem;
	}

	/**
	 * This method returns an array of SimpleItem objects with specific category id
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function getAllForms()
	{
		$this->itemMapper->alloc($this->config->imFormsCategoryId);
		$simpleItems = $this->itemMapper->getSimpleItems();

		if(!$simpleItems) return null;

		$this->itemMapper->total = $this->itemMapper->countItems($simpleItems);

		return $simpleItems;
	}

	/**
	 * This method initializes and returns "new" or an already "existing" item
	 *
	 * @return \Item
	 */
	public function getItemForEdit($catid, $itemid = 0)
	{
		$editid = ($itemid > 0) ? (int) $itemid : 0;
		if($editid > 0) { $this->itemMapper->limitedInit($catid, $editid); }
		return !empty($this->itemMapper->items[$editid]) ? $this->itemMapper->items[$editid] : new \Item($catid);
	}

	/**
	 * This method returns an item, if it already exists otherwise null is returned
	 *
	 * @param $catid
	 * @param $itemid
	 *
	 * @return \Item | null
	 */
	public function getItem($catid, $itemid) {
		$this->itemMapper->limitedInit($catid, $itemid);
		return !empty($this->itemMapper->items[$itemid]) ? $this->itemMapper->items[$itemid] : null;
	}

	/**
	 * Returns an ImFormsForm object
	 *
	 * @param $catid
	 * @param $itemid
	 *
	 * @return null | object - ImFormsForm object
	 */
	public function getFormById($itemid)
	{
		$this->itemMapper->alloc($this->config->imFormsCategoryId);
		$item = $this->itemMapper->getSimpleItem((int)$itemid);
		return !empty($item) ? unserialize(base64_decode($item->data)) : new ImFormsForm();
	}

	/**
	 * Returns an ImFormsForm object
	 *
	 * @param $catid
	 * @param $itemid
	 *
	 * @return null | object - ImFormsForm object
	 */
	public function getFormByName($name)
	{
		$this->itemMapper->alloc($this->config->imFormsCategoryId);
		$item = $this->itemMapper->getSimpleItem('name='.$this->imanager->sanitizer->pageName($name));
		return !empty($item) ? unserialize(base64_decode($item->data)) : new ImFormsForm();
	}


	public function unserialize(\SimpleItem $item) {
		return !empty($item) ? unserialize(base64_decode($item->data)) : new ImFormsForm();
	}

	/**
	 * This method will called after clicking on Save Item/Category button
	 *
	 * @return bool
	 */
	public function saveForm(ImFormsForm $form, $form_id)
	{
		$data = base64_encode(serialize($form));

		$item = $this->getItemForEdit($this->config->imFormsCategoryId, $form_id);

		if(!$item) return false;

		// Clean up cached images
		$this->imanager->cleanUpCachedFiles($item);
		$item->name = $form->name;
		$item->active = 1;
		if($item->setFieldValue('data', $data, false)) {
			if($item->save()) {
				// useAllocater is activated
				if($this->imanager->config->useAllocater == true)
				{
					if($this->itemMapper->alloc($item->categoryid) !== true)
					{
						$this->itemMapper->init($this->config->imFormsCategoryId);
						if(!empty($this->itemMapper->items))
						{
							$this->itemMapper->simplifyBunch($this->itemMapper->items);
							$this->itemMapper->save();
						}
					}
					$this->itemMapper->simplify($item);
					$this->itemMapper->save();
				}
				return true;
			}
		}
		return false;
	}


	public function activateItem(\Item $item, $activate = 1) {
		$this->imanager->cleanUpCachedFiles($item);
		$item->active = $activate;
		if($item->save()) {
			// useAllocater is activated
			if($this->imanager->config->useAllocater == true)
			{
				if($this->itemMapper->alloc($item->categoryid) !== true)
				{
					$this->itemMapper->init($item->categoryid);
					if(!empty($this->itemMapper->items)) {
						$this->itemMapper->simplifyBunch($this->itemMapper->items);
						$this->itemMapper->save();
					}
				}
				$this->itemMapper->simplify($item);
				$this->itemMapper->save();
			}
			return true;
		}
		return false;
	}

	/**
	 * Delete a single item
	 *
	 * @return bool
	 */
	public function deleteItem($itemid) {
		if(!empty($itemid)) return $this->imanager->deleteItem($itemid, $this->config->imFormsCategoryId);
	}

	/**
	 * Returns for humans readable name of an form element
	 *
	 * @param $element
	 *
	 * @return mixed
	 */
	public function getElementName($element) {
		return str_replace('ImForms\ImForms', '', get_class($element));
	}

	/**
	 * Search for a specific form element in the objects by id
	 *
	 * @param $element_id
	 * @param $element_class
	 * @param $elements
	 *
	 * @return element | null
	 */
	public function findElement($element_id, $element_class = null, $elements)
	{
		if(!empty($elements)) {
			foreach($elements as $key => $element)
			{
				if($element_class) { if($key == $element_id && get_class($element) == $element_class) return $element;}
				else { if($key == $element_id) return $element; }

				if(!empty($element->elements)) {
					$find = $this->findElement($element_id, $element_class, $element->elements);
					if($find) return $find;
				}
			}
		}
		return null;
	}

	/**
	 * Search for a specific form element in the objects by element name
	 *
	 * @param $element_id
	 * @param $element_class
	 * @param $elements
	 *
	 * @return element | null
	 */
	public function findElementByFieldName($element_name, $elements)
	{
		if(!empty($elements)) {
			foreach($elements as $key => $element)
			{
				if($this->getElementName($element) == $element_name) return $element;

				if(!empty($element->elements)) {
					$find = $this->findElementByFieldName($element_name, $element->elements);
					if($find) return $find;
				}
			}
		}
		return null;
	}

	/**
	 * Search for a specific form element in the objects by element attribut
	 *
	 * @param $element_id
	 * @param $element_class
	 * @param $elements
	 *
	 * @return element | null
	 */
	public function findElementByAttribut($attribut_name, $attribut_value, $elements)
	{
		if(!empty($elements)) {
			foreach($elements as $key => $element)
			{
				if(isset($element->{$attribut_name}) && $element->{$attribut_name} == $attribut_value) return $element;

				if(!empty($element->elements)) {
					$find = $this->findElementByAttribut($attribut_name, $attribut_value, $element->elements);
					if($find) return $find;
				}
			}
		}
		return null;
	}

	public function getElementsClassName($suffix) { return __NAMESPACE__."\ImForms$suffix"; }

	/**
	 * [DONE] TODO: move it to processor
	 *
	 * @param $suffix
	 * @param $parent
	 *
	 * @return mixed
	 */
	public function build($suffix, $parent)
	{
		$className = $this->getElementsClassName($suffix);
		if(class_exists($className)) {
			$element = new $className();
			$element->parent_id = ($parent == 'null') ? null : (int)$parent;
			return $element;
		}
	}

	/**
	 * [DONE] Todo: Same here, it's a processor method
	 *
	 * @param $parent
	 * @param $element
	 * @param $element_id
	 *
	 * @return mixed
	 */
	public function assemble($parent, $element, $element_id) {
		$parent->add($element, $element_id);
		return $parent;
	}
}