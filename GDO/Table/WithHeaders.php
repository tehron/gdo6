<?php
namespace GDO\Table;
use GDO\Core\GDT_Fields;
use GDO\DB\ArrayResult;
use GDO\Util\Common;
use GDO\Core\GDT;
/**
 * - A trait for tables and list which adds an extra headers variable. This has to be a \GDO\Core\GDT_Fields.
 * - Implements @\GDO\Core\ArrayResult multisort for use in @\GDO\Table\MethodTable.
 * @author gizmore
 * @since 6.05
 * @version 6.05
 */
trait WithHeaders
{
    ###############
    ### Headers ###
    ###############
    /**
     * @var \GDO\Core\GDT_Fields
     */
    public $headers;
    private function makeHeaders() { if (!$this->headers) $this->headers = GDT_Fields::make(); return $this->headers; }
    public function headers(GDT_Fields $headers) { $this->headers = $headers; }
    public function headersWith(array $fields) { return $this->addHeaders($fields); }
    public function addHeaders(array $fields) { return $this->makeHeaders()->addFields($fields); }
    public function addHeader(GDT $field) { return $this->makeHeaders()->addField($field); }
    
    ###############
    ### Sorting ###
    ###############
    /**
     * PHP Sorting is unstable.
     * This method does a stable multisort on an ArrayResult.
     * @param ArrayResult $result
     */
    public function multisort(ArrayResult $result)
    {
        $sort = $this->make_cmp(Common::getRequestArray($this->headers->name));
        usort($result->data, $sort);
    }
    private function make_cmp(array $sorting)
    {
        $headers = $this->headers;
        return function ($a, $b) use (&$sorting, &$headers)
        {
            foreach ($sorting as $column => $sortDir)
            {
                $diff = $headers->getField($column)->gdoCompare($a, $b);
                if ($diff !== 0)
                {
                    return $sortDir === '1' ? $diff : -$diff;
                }
            }
            return 0;
        };
    }
}