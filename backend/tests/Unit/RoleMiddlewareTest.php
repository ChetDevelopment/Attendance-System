<?php

namespace Tests\Unit;

use App\Http\Middleware\RoleMiddleware;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    public function test_it_allows_education_team_role_on_education_routes(): void
    {
        $middleware = new RoleMiddleware();
        $request = Request::create('/api/education/dashboard/stats', 'GET');
        $request->setUserResolver(function () {
            $user = new User();
            $user->setRelation('role', new Role([
                'name' => 'Education Team',
                'slug' => 'education_team',
            ]));

            return $user;
        });

        $response = $middleware->handle($request, fn () => response('ok'), 'education');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }

    public function test_it_blocks_unmatched_roles(): void
    {
        $middleware = new RoleMiddleware();
        $request = Request::create('/api/admin/dashboard/data', 'GET');
        $request->setUserResolver(function () {
            $user = new User();
            $user->setRelation('role', new Role([
                'name' => 'Teacher',
                'slug' => 'teacher',
            ]));

            return $user;
        });

        $response = $middleware->handle($request, fn () => response('ok'), 'admin');

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertSame('{"message":"Forbidden"}', $response->getContent());
    }
}
