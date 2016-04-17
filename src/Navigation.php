<?php

namespace Inoplate\Navigation;

use Roseffendi\Authis\Authis;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Routing\UrlGenerator;

class Navigation
{
    /**
     * @var array
     */
    protected $menus = [];

    /**
     * @var Roseffendi\Authis\Authis
     */
    protected $authis;

    /**
     * @var Illuminate\Contracts\Routing\Registrar
     */
    protected $route;

    /**
     * @var Illuminate\Contracts\Routing\UrlGenerator
     */
    protected $url;

    /**
     * @var Illuminate\Contracts\Events\Dispatcher;
     */
    protected $dispather;

    /**
     * Create new navigator instance
     * 
     * @param Authis       $authis
     * @param Registrar    $route
     * @param UrlGenerator $url
     * @param Dispatcher   $dispather
     */
    public function __construct(
        Authis $authis, 
        Registrar $route, 
        UrlGenerator $url, 
        Dispatcher $dispather
    ) {
        $this->authis = $authis;
        $this->route = $route;
        $this->url = $url;
        $this->dispather = $dispather;
    }

    /**
     * Register menu
     * 
     * @param  array $menus
     */
    public function register($menus)
    {
        foreach ($menus as $section => $menu) {
            $this->menus[$section] = isset($this->menus[$section]) ? $this->menus[$section] : [];

            foreach ($menu as $unit) {
                $order = isset($unit['order']) ? $unit['order'] : count($this->menus[$section]);
                $unit['order'] = $order;

                $this->menus[$section][] = $unit;
            }
        }
    }

    /**
     * Retrieve all menu
     * 
     * @return array
     */
    public function all()
    {
        $return = [];
        foreach ($this->menus as $section => $menu) {
            $return[$section] = $this->section($section);
        }

        return $return;
    }

    /**
     * Retrieve menu by section
     * 
     * @param  string $section
     * @return array
     */
    public function section($section)
    {
        $return = [];
        if(isset($this->menus[$section])) {
            foreach ($this->menus[$section] as $menu) {
                $populated = $this->populate($menu);

                if(!is_null($populated)) {
                    $return[] = $populated;
                }
            }
        }

        return $this->sort($return);
    }

    /**
     * Sort menus
     * 
     * @param  array $menu
     * @return array
     */
    protected function sort($menu)
    {
        usort($menu, function($a, $b){
            return $a['order'] - $b['order'];
        });

        return $menu;
    }

    /**
     * Populate menus
     * 
     * @param  array $menu
     * @return array|null
     */
    protected function populate($menu)
    {
        if((isset($menu['permission']))&&($menu['permission'])&&(!$this->authorize($menu['permission'])))
            return null;

        $menu['url'] = $this->normalize($menu['url']);

        if((isset($menu['hookable'])) && ($menu['hookable'])) {
            // This menu is hookable lets find the hooked

            // Okay, the menu has id.
            // Go get child from hooks
            if((isset($menu['id'])) && ($menu['id'])) {

                // Yippeey we get child from hook
                $childHook = $this->extractChildHook($this->dispather->fire('menu.'.$menu['id']));

                if(isset($menu['childs'])) {
                    // This menu already has child, so make them bro
                    
                    $menu['childs'] = array_merge($menu['childs'], $childHook);
                } else {
                    // This menu have no childs yet :( , so give him some childs :)

                    $menu['childs'] = $childHook;
                }
            }            
        }

        if(isset($menu['childs'])) {
            // Horrey we have some childs
            $childs = [];
            foreach ($menu['childs'] as $child) {
                if(!is_null($child = $this->populate($child))) {
                    $childs[] = $child;
                }
            }

            // Heyy kids are you eliminated ?
            
            if(count($childs)) {
                $menu['childs'] = $childs;
            }else {
                // Damn, iam eliminated :(
                
                unset($menu['childs']);
            }
            
        }

        return $menu;
    }

    /**
     * Determine if current user is authorized
     * 
     * @param  string $permission
     * @return boolean
     */
    protected function authorize($resource)
    {
        return $this->authis->check($resource);
    }

    /**
     * Normalize route to url
     * 
     * @param  string $url
     * @return string
     */
    protected function normalize($url)
    {
        return $this->route->has($url) ? $this->url->route($url) : $url;
    }

    /**
     * Extract child from hook
     * 
     * @param  array $childHooks
     * @return array
     */
    protected function extractChildHook($childHooks)
    {
        $childs = [];

        foreach ($childHooks as $childHook) {
            foreach ($childHook as $child) {
                $childs[] = $child;
            }
        }

        return $childs;
    }
}
