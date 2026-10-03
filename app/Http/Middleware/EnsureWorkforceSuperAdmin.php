<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
class EnsureWorkforceSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user=$request->user();
        abort_unless($user,401);
        $raw=DB::table('system_settings')->where('key','workforce.super_admin_roles')->value('value');
        $allowed=json_decode($raw ?: '["super_admin","admin_group","admin"]',true) ?: [];
        $roles=DB::table('user_roles')->where('user_id',$user->id)->pluck('role')->all();
        abort_unless(count(array_intersect($allowed,$roles))>0,403,'Super Admin permission required.');
        return $next($request);
    }
}
