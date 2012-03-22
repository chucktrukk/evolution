<?php

class ModxTree
{

    public function __construct($modx, $level = 0)
    {
        $this->modx = $modx;
        $this->level = $level;
        
        $this->stack = array();
        
        $this->buildStack($this->stack, $this->level);
        $this->leveled_stack = $this->buildLeveledStack($this->stack);
    }
    
    public function buildStack( &$stack = array(), $level = 0 )
    {
        if(empty($stack)) {
            foreach($this->modx->getChildIds($level, 1) as $alias => $docId)
            {
                $stack[$level][$docId] = array('id' => $docId, 'parent' => 0, 'alias' => $alias, 'level' => $level);
            }
            $level = $level + 1;
        }
        
        $tmp = array();
        foreach($stack[$level - 1] as $doc => $alias) {
            foreach($this->modx->getChildIds($doc, 1) as $alias => $docId) {
                $stack[$level][$docId] = array('id' => $docId, 'parent' => $doc, 'alias' => $alias, 'level' => $level);
            }
        }
        
        if( ! empty($stack[$level]) ) 
        {
            $this->buildStack($stack, $level + 1);
        }
    }
    
    public function buildLeveledStack($flat_stack)
    {
        $output_stack = $flat_stack;
        $levels = count($output_stack);

        while($levels > 1)
        {
            $element = array_pop($output_stack);

            foreach($element as $doc => $attr)
            {
                $parent = $attr['parent'];
                $pLevel = $attr['level'] - 1;
        
                $output_stack[$pLevel][$parent]['__children'][$attr['id']] = $attr;
            }
    
            $levels--;
        }
        
        return array_shift($output_stack);
    }
    
    public function buildArrayForTiny($leveled_stack, &$outputArray)
    {
        foreach($leveled_stack as $resource)
        {
            
            $first_slash_position = strpos($resource['alias'], '/');
            $alias_without_domain = substr($resource['alias'], $first_slash_position + 1);
            
            $outputArray[] = array(
                'pagetitle' => $alias_without_domain,
                'id'        => $resource['id'],
                'menutitle' => $alias_without_domain,
            );
            
            if( isset($resource['__children']) && ! empty($resource['__children']) )
            {
              $this->buildArrayForTiny($resource['__children'], $outputArray);
            }
        }
    }

}