<?php
namespace App\Http\Middleware;
use App\Services\FeatureToggleService;
use Closure;
use Illuminate\Http\Request;
class SetUserLocale { public function handle(Request $request,Closure $next){ if(FeatureToggleService::enabled('trilingual_ui')){$locale=$request->user()?->locale?:$request->session()->get('locale','en');app()->setLocale(in_array($locale,['en','si','ta'],true)?$locale:'en');}else app()->setLocale('en'); return $next($request);} }
