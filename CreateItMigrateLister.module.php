<?php

namespace ProcessWire;

use PDOException;

/**
 * @author Christopher Cookson
 * @license MIT
 * @link https://www.createit.co.nz
 */
class CreateItMigrateLister extends WireData implements Module
{

	public static function getModuleInfo()
	{
		return [
			'title' => 'CreateItMigrateLister',
			'version' => '0.0.4',
			'summary' => 'Lister Pro backup and restore for ProcessWire',
			'autoload' => 'template=admin',
			'singular' => true,
			'icon' => 'magic',
			'requires' => 'ProcessPageListerPro'
		];
	}

	public function init()
	{
		$modules = wire()->modules;
		$listerPro = $modules->get('ProcessPageListerPro');

		//$this->addHookBefore("ProcessPageListerPro::executeConfig", $this, "showCopyCode"); //InputfieldForm::render
		$this->addHookBefore("InputfieldForm::render", $this, "showListerCode");
		$modules->addHookAfter('saveModuleConfigData', $this, 'processConfigActions');
	}

	/**
	 * Show edit info on field and template edit screen
	 * @return void
	 */
	public function showListerCode(HookEvent $event)
	{
		$form = $event->object;
		//bd($event->object);
		//bd($this->wire()->page;
		$page = $this->wire()->page;

		if ($event->process == 'ProcessPageListerPro' && $page->template == 'admin' && $page->urlSegment == 'config') {
			$lister = $page;
		} elseif ($event->process == 'ProcessModule' && $page->template == 'admin') {
			$this->importListerCode($event);
			return;
		} else {
			return;
		}

		$existing = $form->get('defaultSelector');
		// early exit (eg when changing fieldtype)
		if (!$existing)
			return;

		$form->add([
			'name' => '_ListerProCopyInfo',
			'type' => 'markup',
			'label' => 'Lister Code',
			'description' => 'This is the code you can export',
			'value' => "<pre><code>" . $this->getListerCode($lister) . "</code></pre>",
			'collapsed' => Inputfield::collapsedYes,
			'icon' => 'code',
		]);
		$f = $form->get('_ListerProCopyInfo');
		$form->remove($f);
		$form->insertBefore($f, $existing);
	}

	public function importListerCode(HookEvent $event)
	{
		$form = $event->object;
		$page = $this->wire()->page;
		$lister = $this->wire()->page;

		$existing = $form->get('_new_lister_title');
		// early exit (eg when changing fieldtype)
		if (!$existing)
			return;


		/** @var InputfieldTextarea $f */
		$f = $this->modules()->InputfieldTextarea;
		$f->attr('name', 'import_data');
		$f->label = $this->_x('Import', 'button');
		$f->icon = 'paste';
		$f->description = $this->_('Paste in the data from an exported lister.');
		$f->description .= "\n**Experimental/beta feature: database backup recommended for safety.**";
		$f->notes = $this->_('Copy the export data from another lister and then paste into the box above with CTRL-V or CMD-V.');
		$form->add($f);
		$f = $form->get('import_data');
		$form->remove($f);
		$form->insertAfter($f, $existing);
	}

	/**
	 * Return the Lister data for $Page as JSON 
	 *
	 * @param Page $page
	 * @param $newListerTitle
	 * @return Page Returns the new Lister page
	 *
	 */
	public function getListerCode(Page $page)
	{
		$modules = $this->wire()->modules;

		$configData = $modules->getModuleConfigData('ProcessPageListerPro');
		$settings = isset($configData['settings'][$page->name]) ? $configData['settings'][$page->name] : array();

		//$code = $this->varexport($settings);
		$code = wireEncodeJSON($settings);
		return $code;
	}

	/**
	 * @param string $json 
	 * @return mixed 
	 * @throws WireException 
	 * @throws PDOException 
	 */
	public function importLister(string $json)
	{
		$modules = $this->wire()->modules;
		$lister = $this->wire()->modules('ProcessPageListerPro');
		$sanitizer = $this->wire()->sanitizer;

		$data = is_array($json) ? $json : wireDecodeJSON($json);
		if (!$data) throw new WireException("Invalid import data");

		$newPage = $lister->addNewLister($data['pagename']);
		if (!$newPage->id) return $newPage; // NullPage

		$configData = $modules->getModuleConfigData('ProcessPageListerPro');

		$settings = isset($data) ? $data : array();

		//$settings = isset($configData['settings'][$page->name]) ? $configData['settings'][$page->name] : array();
		$settings['pagename'] = $data['pagename'];
		$configData['settings'][$data['pagename']] = $settings;
		$modules->saveModuleConfigData('ProcessPageListerPro', $configData);
		$this->message(sprintf($this->_('Imported Lister: %1$s'), $data['pagename']));
		return $newPage;
	}

	/**
	 * @param HookEvent $e 
	 * @return void 
	 * @throws WireException 
	 * @throws WirePermissionException 
	 * @throws PDOException 
	 */
	public function processConfigActions(HookEvent $e)
	{
		if (!$e->arguments(0) == 'ProcessPageListerPro')
			return;
		$input = $this->wire()->input;

		static $level = 0;
		$level++;
		if ($level > 1) return;

		// check for Lister Data to import
		//$title = $input->post('_new_lister_title');
		$data = $input->post('import_data');
		if ($data) {
			$this->importLister($data);
		}
	}

	/**
	 * PHP var_export() with short array syntax (square brackets) indented 2 spaces.
	 *
	 * NOTE: The only issue is when a string value has `=>\n[`, it will get converted to `=> [`
	 * @link https://www.php.net/manual/en/function.var-export.php
	 */
	function varexport($expression, $return = TRUE)
	{
		$export = var_export($expression, TRUE);
		$patterns = [
			"/array \(/" => '[',
			"/^([ ]*)\)(,?)$/m" => '$1]$2',
			"/=>[ ]?\n[ ]+\[/" => '=> [',
			"/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
		];
		$export = preg_replace(array_keys($patterns), array_values($patterns), $export);
		if ((bool) $return)
			return $export;
		else
			echo $export;
	}
}
