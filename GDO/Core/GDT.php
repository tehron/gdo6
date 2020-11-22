<?php
namespace GDO\Core;

use GDO\DB\Query;
use GDO\Table\GDT_Table;
use GDO\Form\GDT_Form;
use GDO\DB\GDT_String;
use GDO\UI\WithIcon;
use GDO\Util\Strings;

/**
 * Base class for all GDT.
 * 
 * To implement a new GDT inherit this class and override rendering methods and validation.
 * 
 * There are a few traits that offer features like completion, html attributes or tooltips.
 * Most GDT either are Database enabled (GDT_String, GDT_Int, GDT_Enum) or mostly used for rendering like (GDT_Title, GDT_Link, etc...)
 * 
 * @author gizmore
 * @version 6.10
 * @since 6.00
 * 
 * @see \GDO\DB\GDT_Int - Database supporting integer baseclass
 * @see \GDO\DB\GDT_String - Database supporting string baseclass
 * @see \GDO\DB\GDT_Enum - Database supporting enum class
 * @see \GDO\UI\GDT_Paragraph - Simple text rendering
 * @see \GDO\Table\MethodQueryList - highest class in table methods.
 */
abstract class GDT
{
	use WithName;
	use WithIcon;
	
	# Same as $gdo but always set and always the table.
	/** @var $gdtTable GDO **/
	public $gdtTable;
	/**
	 * @var GDO
	 */
	public $gdo; # current row / gdo
	public $name; # html id
	public $var; # String representation
	public $initial; # Initial var
	public $unique; # DB
	public $primary; # DB
	public $readable = true; # can see
	public $writable = true; # user can change?
	public $editable = true; # user can change?
	public $hidden = false; # hide in tables, forms, lists and cards.
	public $notNull = false; # DB
	public $orderable = false; # GDT_Table
	public $filterable = false; # GDT_Table
	public $searchable = false; # GDT_Table
	
	###############
	### Factory ###
	###############
	private static $nameNr = 1;
	public static function nextName() { return 'gdo-'.(self::$nameNr++); }
	public function hasName() { return substr($this->name, 0, 4) !== 'gdo-'; }

	###############
	### Factory ###
	###############
	public static $COUNT = 0;
	/**
	 * Create a GDT instance.
	 * @param string $name
	 * @return static
	 */
	public static function make($name=null)
	{
	    self::$COUNT++;
		$obj = new static();
		return $obj->name($name ? $name : $obj->name);
	}
	
	### stats
	public function __wakeup() { self::$COUNT++; }
	
	############
	### Name ###
	############
	public function name($name=null) { $this->name = $name === null ? self::nextName() : $name; return $this; }
	public function htmlName() { return Strings::startsWith($this->name, 'gdo-') ? '' :  sprintf(' name="%s"', $this->name); }
	
	private static $classNameCache = [];
	public function htmlClass()
	{
	    if (!isset(self::$classNameCache[static::class]))
	    {
	        self::$classNameCache[static::class] = $cache = " gdo-".strtolower($this->gdoShortName());
	        return $cache;
	    }
	    return self::$classNameCache[static::class];
	}
	
	##############
	### FormID ###
	##############
	public function id() { return (GDT_Form::$CURRENT?GDT_Form::$CURRENT->name:'')."_".$this->name; }
	public function htmlID() { return sprintf('id="%s"', $this->id()); }
	public function htmlForID() { return sprintf('for="%s"', $this->id()); }
	
	###########
	### RWE ###
	###########
	public function readable($readable) { $this->readable = $readable; return $this; }
	public function writable($writable) { $this->writable = $writable; return $this; }
	public function editable($editable) { $this->editable = $editable; return $this->writable($editable); }
	public function hidden($hidden=true) { $this->hidden = $hidden; return $this;}

	#############
	### Error ###
	#############
	public $error;
	public function error($key, array $args=null) { return $this->rawError(t($key, $args)); }
	public function rawError($html=null) { $this->error = $html; return false; }
	public function hasError() { return is_string($this->error); }
	public function htmlError() { return $this->error ? ('<div class="gdo-form-error">' . $this->error . '</div>') : ''; }
	public function classError()
	{
		$class = ' '.str_replace('_', '-', strtolower($this->gdoShortName()));
		if ($this->notNull) $class .= ' gdo-required';
		if ($this->hasError()) $class .= ' gdo-has-error';
		return $class;
	}
	
	###################
	### CRUD Events ###
	###################
	public function gdoBeforeCreate(Query $query) {}
	public function gdoBeforeRead(Query $query) {}
	public function gdoBeforeUpdate(Query $query) {}
	public function gdoBeforeDelete(Query $query) {}
	public function gdoAfterCreate() {}
	public function gdoAfterRead() {}
	public function gdoAfterUpdate() {}
	public function gdoAfterDelete() {}
	
	#############
	### Table ###
	#############
	public function gdtTable(GDO $table) { $this->gdtTable = $table; return $this; }
	
	#################
	### Var/Value ###
	#################
	public function gdo(GDO $gdo)
	{
	    $this->gdo = $gdo;
	    return !$gdo->isTable() ? $this->setGDOData($gdo) : $this->var($this->initial);
	}
	public function var($var=null) { $this->var = $var === null ? null : (string)$var; return $this; }
	public function value($value) { $this->var = $this->toVar($value); return $this; }
	public function toVar($value) { return ($value === null) || ($value === '') ? null : (string) $value; }
	public function toValue($var) { return ($var === null) || ($var === '') ? null : (string) $var; }
	public function hasVar() { return !!$this->getVar(); }
	public function getVar() { $form = $this->formVariable(); return $form ? $this->getRequestVar($form, $this->var) : $this->var; }
	public function getParameterVar() { return $this->getRequestVar(null, $this->var); }
	public function getParameterValue() { return $this->toValue($this->getParameterVar()); }
	public function getValue() { return $this->toValue($this->getVar()); }
	public function initial($var=null) { $this->initial = $var === null ? null : (string)$var; return $this->var($var); }
	public function initialValue($value) { return $this->initial($this->toVar($value)); }
	public function displayVar() { return html($this->getVar()); }
	public function displayValue($var) { return html($var); }
	public function displayJSON() { return json_encode($this->renderJSON()); }

	public function getFields() {}
	public function hasChanged() { return $this->initial !== $this->getVar(); }
	public function getValidationValue() { return $this->getValue(); }
	
	public function isSerializable() { return true; }
	public function isPrimary() { return false; }
	
	###################
	### Form Naming ###
	###################
	public function formVariable() { return GDT_Form::$CURRENT ? GDT_Form::$CURRENT->name : null; }
	public function formName() { return GDT_Form::$CURRENT ? sprintf('%s[%s]', $this->formVariable(), $this->name) : $this->name; }
	public function htmlFormName() { return sprintf(" name=\"%s\"", $this->formName()); }
	
	#################
	### GDO Value ###
	#################
	public function blankData() { return [$this->name => $this->initial]; }
	public function getGDOData() {}
	public function setGDOVar($var) { if ($this->gdo) $this->gdo->setVar($this->name, $var); return $this; }
	public function setGDOValue($value) { return $this->setGDOVar($this->toVar($value)); }
	
	/**
	 * @param GDO $gdo
	 * @return GDT
	 */
	public function setGDOData(GDO $gdo)
	{
	    if ($gdo->isTable())
	    {
	        $this->var = $this->initial;
	    }
		elseif ($gdo->hasVar($this->name))
		{
			$this->var = $gdo->getVar($this->name);
		}
		return $this;
	}
	
	
	/**
	 * Get a param for this GDT from $_REQUEST.
	 * $firstlevel can be like o1[o][field]
	 * $name hackery can be like iso][en][field.
	 * 
	 * $firstLevel usually is [form]
	 * Override default with simple get param.
	 * 
	 * @param string $firstLevel
	 * @param string $default
	 * @param string $name
	 * 
	 * @return string
	 */
	public function getRequestVar($firstLevel=null, $default=null, $name=null)
	{
		$name = $name === null ? $this->name : $name;
		
		# Bring hackery in the firstlevel format
		if (strpos($name, ']'))
		{
		    $parts = explode('][', $name);
		    $name = array_pop($parts);
		    foreach ($parts as $part)
		    {
		        $firstLevel .= "[{$part}]";
		    }
		}
		
		$arr = $_REQUEST;
		if ($firstLevel)
		{
		    $next = Strings::substrTo($firstLevel, '[', $firstLevel);
	        $next = trim($next, '[]');
	        if (!isset($arr[$next]))
	        {
	            return $default;
	        }
	        $arr = $arr[$next];
	        $firstLevel = '[' . Strings::substrFrom($firstLevel, '[');
		}
		while ($firstLevel)
		{
    		if (isset($arr[$name]))
    		{
    		    return $arr[$name];
    		}
    		if (is_array($arr))
    		{
    		    $next = Strings::substrTo($firstLevel, ']');
    		    $next = ltrim($next, '[');
    		    if (!isset($arr[$next]))
    		    {
    		        return $default;
    		    }
    		    $arr = $arr[$next];
    		    $firstLevel = Strings::substrFrom($firstLevel, ']');
    		}
    		else
    		{
    		    break;
    		}
		}
		return isset($arr[$name]) ? $arr[$name] : $default;
	}
	
	##############
	### Render ###
	##############
	public function render() { return $this->renderCell(); }
	public function renderPDF() { return $this->renderCard(); }
	public function renderCell() { return $this->renderCellSpan($this->getVar()); }
	public function renderCellSpan($var) { return sprintf('<span class="%s">%s</span>', $this->htmlClass(), html($var)); }
	public function renderCard() { return GDT_Template::php('Core', 'card/gdt.php', ['gdt'=>$this]); }
	public function renderList() { return $this->render(); }
	public function renderForm() { return $this->render(); }
	public function renderFilter($f) {}
	public function renderHeader() {}
	public function renderChoice($choice) { return is_object($choice) ? $choice->renderChoice() : $choice; }
	public function renderJSON() { return [$this->name => $this->var]; }
	
	public $labelArgs;
	public function labelArgs(...$labelArgs) { $this->labelArgs = $labelArgs; return $this; }
	public function displayLabel() { return t($this->name, $this->labelArgs); }
	
	# Render debug data by default.
	private function renderDebug() { return print_r($this, true); }
	
	################
	### Validate ###
	################
	public function notNull($notNull=true) { $this->notNull = $notNull; return $this; }
	public function errorNotNull() { return $this->error('err_not_null'); }
	public function onValidated() {}
	public function validate($value)
	{
		if ( ($value === null) && ($this->notNull) )
		{
			return $this->errorNotNull();
		}
		return true;
	}
	
	############
	### Sort ###
	############
	public function displayTableOrder(GDT_Table $table) {}
	private static $SORT_COLUMN;
	public function sort(array &$array, $ascending=true)
	{
		self::$SORT_COLUMN = $this;
		uasort($array, function(GDO $a, GDO $b) {
			return self::$SORT_COLUMN->gdoCompare($a, $b);
		});
		return $ascending ? $array : array_reverse($array, true);
	}
	
	public function gdoCompare(GDO $a, GDO $b)
	{
		return strcasecmp($a->getVar($this->name), $b->getVar($this->name));
	}
	
	#############
	### Order ###
	#############
	public function orderable($orderable=true) { $this->orderable = $orderable; return $this; }
	
	public $orderField;
	public function orderField($orderField) { $this->orderField = $orderField; return $this; }
	public function orderFieldName() { return $this->orderField ? $this->orderField : $this->name; }
	
	public $orderDefaultAsc = true;
	public function orderDefaultAsc($defaultAsc=true) { $this->orderDefaultAsc = $defaultAsc; return $this; }
	public function orderDefaultDesc($defaultDesc=true) { $this->orderDefaultAsc = !$defaultDesc; return $this; }

	public function orderVar($rq=null) { return $this->getRequestVar("$rq[o]", $this->initial, $this->filterField ? $this->filterField : $this->name); }
	
	##############
	### Filter ###
	##############
	public $searchField;
	public function searchable($searchable=true) { $this->searchable = $searchable; return  $this; }
	
	public function filterable($filterable=true) { $this->filterable = $filterable; return  $this; }
	
	public $filterField;
	public function filterField($filterField) { $this->filterField = $filterField; return $this->searchable(); }

	public function filterVar($rq=null) { return $this->getRequestVar("{$rq}[f]", $this->initial, $this->filterField ? $this->filterField : $this->name); }
	
	/**
	 * Filter decorator function for database queries.
	 * @see GDT_String
	 */
	public function filterQuery(Query $query, $rq=null) {}

	/**
	 * Extend query with searching for a term. Used in quicksearch.
	 * Search looks in all searchable columns for an OR match.
	 * Objects JOIN their foreign tables during this.
	 * @param Query $query
	 * @param string $searchTerm
	 */
	public function searchQuery(Query $query, $searchTerm, $first) {}

	/**
	 * Build a search condition.
	 * @param string $searchTerm
	 */
	public function searchCondition($searchTerm, $fkTable=null) {}
	
	/**
	 * Filter for entities.
	 * @see GDT_String
	 * @param GDO $gdo
	 */
	public function filterGDO(GDO $gdo, $rq) {}
	
	################
	### Database ###
	################
	public function gdoColumnDefine() {}
	
	##############
	### Config ###
	##############
	public function displayConfigJSON() { return json_encode($this->configJSON(), JSON_PRETTY_PRINT); }

	/**
	 * Expose all fields to JSON config.
	 * @return array
	 */
	public function configJSON()
	{
	    return array(
	        'id' => $this->id(),
	        'name' => $this->name,
	        'type' => $this->gdoClassName(),
	        'var' => $this->var,
	        'icon' => $this->icon,
	        'error' => $this->error,
	        'initial' => $this->initial,
	        'unique' => $this->unique,
	        'primary' => $this->primary,
	        'notNull' => $this->notNull,
	        'readable' => $this->readable,
	        'writable' => $this->writable,
	        'editable' => $this->editable,
	        'orderable' => $this->orderable,
	        'filterable' => $this->filterable,
	        'searchable' => $this->searchable,
	    );
	}

}
