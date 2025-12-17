<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Sede;
use App\Models\Tipodocumento;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class ListaUsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with(['sede', 'permissions', 'roles', 'equipos']);
        if (auth()->user()->hasRole('sistema')) {
        } else {
            $users->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'sistema');
            });
        }
        $users = $users->orderByDesc('id')->get();

        return view('sistema.lista_usuario.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (request('view') == 'create') {
            $sedes = Sede::all();
            $roles = Role::all();
            $equipos = Equipo::all();
            $tipodocumentos = Tipodocumento::all();

            return view('sistema.lista_usuario.create', compact(
                'sedes',
                'roles',
                'equipos',
                'tipodocumentos'
            ));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $view = request('view');
        if ($view === 'store') {
            $request->validate([
                    'first_name' => 'required',
                    'first_surname' => 'required',
                    'personal_email' => 'required|email',
                    'tipodocumento_id' => 'required',
                    'identity_document' => 'required',
                    'sede_id' => 'required',
                    'role_id' => 'required',
                ],
                [
                    'first_name.required' => 'El "Nombre" es obligatorio.',
                    'first_surname.required' => 'El "Apellido Paterno" es obligatorio.',
                    'personal_email.required' => 'El "Correo Electrónico" es obligatorio.',
                    'personal_email.email' => 'El "Correo" no es válido, (email@example.com).',
                    'tipodocumento_id.required' => 'El "Tipo de Documento" es obligatorio.',
                    'identity_document.required' => 'El "Nro. de Identificación" es obligatorio.',
                    'sede_id.required' => 'La "Sede" es obligatorio.',
                    'role_id.required' => 'El "Rol" es obligatorio.',
                ]);
            // Si es Ejecutivo, obligar Equipo
            if (request('roleNombre') === 'ejecutivo') {
                $request->validate(
                    [
                        'equipo_id' => 'required',
                    ],
                    [
                        'equipo_id.required' => 'El "Equipo" es obligatorio.',
                    ]
                );
            }
            $role = Role::find(request('role_id'));

            $first_name = strtolower(request('first_name'));
            $second_name = strtolower(request('second_name')) ?? '';
            $first_surname = strtolower(request('first_surname'));
            $second_surname = strtolower(request('second_surname')) ?? '';
            $name = $first_name.' '.$second_name.' '.$first_surname.' '.$second_surname;

            $normalize = fn($text) => iconv('UTF-8', 'ASCII//TRANSLIT', $text);
            $email = strtolower(
                substr(trim($normalize($first_name)), 0, 1) .
                str_replace(' ', '', $normalize($first_surname)) .
                '.wow@relevantperu.com'
            );

            if (User::where('email', $email)->exists()) {
                return response()->json(['error' => 'El usuario ya existe con el correo electrónico ' . $email], 422);
            }

            $user = User::create([
                'first_name' => $first_name,
                'second_name' => $second_name,
                'first_surname' => $first_surname,
                'second_surname' => $second_surname,
                'name' => strtolower($name),
                'personal_phone' => request('personal_phone') ?? '',
                'personal_email' => strtolower(request('personal_email')),
                'tipodocumento_id' => request('tipodocumento_id'),
                'identity_document' => request('identity_document'),
                'email' => $email,
                'password' => bcrypt(request('identity_document')),
                'sede_id' => request('sede_id'),
            ]);
            $user->assignRole($role->name);

            // Si es Ejecutivo, registrar Equipo
            if (request('roleNombre') === 'ejecutivo') {
                $user->equipos()->attach(request('equipo_id'));
            }

            // Establecer el mensaje de éxito en la sesión
            session()->flash('success', 'Usuario creado correctamente');

            return response()->json(['redirect' => true]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $lista_usuario)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $user = User::find($id);
        if (request('view') == 'edit') {
            $sedes = Sede::all();

            return view('sistema.lista_usuario.edit', compact('user', 'sedes'));
        } elseif (request('view') == 'edit-asignar-equipo') {
            $equipos = Equipo::all();

            return view('sistema.lista_usuario.asignar-equipo', compact('user', 'equipos'));
        } elseif (request('view') == 'edit-asignar-rol') {
            $roles = Role::all();

            return view('sistema.lista_usuario.asignar-rol', compact('user', 'roles'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (request('view') == 'update') {
            $request->validate([
                'name' => 'required|bail',
                'email' => 'required|bail',
                'sede_id' => 'required|bail',
            ],
                [
                    'name.required' => 'El "Nombre" es obligatorio.',
                    'email.required' => 'El "Correo" es obligatorio.',
                    'sede_id.required' => 'La "Sede" es obligatorio.',
                ]);
            if (request('password') != '') {
                $request->validate([
                    'password' => 'required|min:8|bail',
                ],
                    [
                        'password.required' => 'La "Contraseña" es obligatorio.',
                        'password.min' => 'La "Contraseña" debe tener 8 dígitos como mínimo.',
                    ]);
            }
            $user = User::find($id);
            $user->name = request('name');
            $user->identity_document = request('identity_document');
            $user->email = request('email');
            $user->sede_id = request('sede_id');
            if (request('password') != '') {
                $user->password = bcrypt(request('password'));
            }
            $user->save();
        } elseif (request('view') == 'update-asignar-equipo') {
            $request->validate([
                'equipo_id' => 'required|bail',
            ],
                [
                    'equipo_id.required' => 'El "Equipo" es obligatorio.',
                ]);
            if (! $user->equipos->isEmpty()) {
                for ($i = 0; $i < count($user->equipos); $i++) {
                    $user->equipos()->detach($user->equipos[$i]->id);
                }
            }
            $user->equipos()->attach(request('equipo_id'));
            if (! $user->clientes->isEmpty()) {
                foreach ($user->clientes as $value) {
                    $cliente = Cliente::find($value->id);
                    $cliente->equipo_id = request('equipo_id');
                    $cliente->save();
                }
            }
        } elseif (request('view') == 'update-asignar-rol') {
            $request->validate([
                'role_id' => 'required|bail',
            ],
                [
                    'role_id.required' => 'El "Rol" es obligatorio.',
                ]);
            $role = Role::find(request('role_id'));
            foreach ($user->getRoleNames() as $value) {
                $user->removeRole($value);
            }
            $user->assignRole($role->name);
        } elseif (request('view') == 'update-user') {
            if (request('password') != '') {
                $request->validate([
                    'password' => 'required|min:8|bail',
                ],
                    [
                        'password.required' => 'La "Contraseña" es obligatorio.',
                        'password.min' => 'La "Contraseña" debe tener 8 dígitos como mínimo.',
                    ]);
                $user = User::find($id);
                $user->password = bcrypt(request('password'));
                $user->save();
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $lista_usuario)
    {
        //
    }
}
