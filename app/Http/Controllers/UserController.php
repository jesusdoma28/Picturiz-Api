<?php

namespace App\Http\Controllers;

use App\Models\Follower;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $email = filter_var(strtolower($request['email']), FILTER_SANITIZE_EMAIL);
        $name = filter_var($request['name'], FILTER_UNSAFE_RAW);
        $last_name = filter_var($request['last_name'], FILTER_UNSAFE_RAW);
        $password = $request['password'];
        $username = filter_var($request['username'], FILTER_UNSAFE_RAW);
        $birthday = filter_var($request['birthday'], FILTER_UNSAFE_RAW);
        $info = filter_var($request['info'], FILTER_UNSAFE_RAW);
        $role_id = filter_var($request['role_id'], FILTER_SANITIZE_NUMBER_INT);

        $haveErrors = false;
        $created = false;

        $errors = [
            'email' => '',
            'name' => '',
            'last_name' => '',
            'password' => '',
            'username' => '',
            'birthday' => '',
        ];

        function busca_edad($fecha_nacimiento)
        {
            $dia = date("d");
            $mes = date("m");
            $ano = date("Y");


            $dianaz = date("d", strtotime($fecha_nacimiento));
            $mesnaz = date("m", strtotime($fecha_nacimiento));
            $anonaz = date("Y", strtotime($fecha_nacimiento));


            //si el mes es el mismo pero el día inferior aun no ha cumplido años, le quitaremos un año al actual

            if (($mesnaz == $mes) && ($dianaz > $dia)) {
                $ano = ($ano - 1);
            }

            //si el mes es superior al actual tampoco habrá cumplido años, por eso le quitamos un año al actual

            if ($mesnaz > $mes) {
                $ano = ($ano - 1);
            }

            //ya no habría mas condiciones, ahora simplemente restamos los años y mostramos el resultado como su edad

            $edad = ($ano - $anonaz);


            return $edad;
        }



        //validar email
        if (preg_match("/^[A-Za-z0-9_\-\.ñÑ]+\@[A-Za-z0-9_\-\.]+\.[A-Za-z]{2,3}$/", $email) && ($email != '' && $email != null)) {
            if (User::where('email', $email)->get()->count() > 0) {
                $errors['email'] = 'El email introducido ya existe.';
                $haveErrors = true;
            }
        } else {
            $errors['email'] = 'El email introducido no es valido o esta vacio. Debe ingresar un email valido.';
            $haveErrors = true;
        }

        if ($name == '' || $name == null) {
            $errors['name'] = 'El nombre no puedo estar vacio.';
            $haveErrors = true;
        }

        //validar password
        if ($password != '' && $password != null) {
            if (!preg_match("/(?=.*[0-9])(?=.*[^a-zA-Z0-9])(?=.*[A-ZÑ])(?=.*[a-zñ])(?=.{8,}).*$/", $password)) {
                $errors['password'] = "La contraseña introducida no cumple los requisitos.\nLos requesitos son:\n
                -Debe tener al menos 8 carácteres.\n
                -Una letra minúscula.\n
                -Una letra mayuscula.\n
                -Un numero.\n
                -Un simbolo.";
                $haveErrors = true;
            }
        } else {
            $errors['password'] = 'La contraseña no puede estar vacia.';
            $haveErrors = true;
        }

        //validar username
        if (preg_match("/^[A-Za-z0-9_\-\.ñÑ]{1,15}$/", $username) && ($username != '' && $username != null)) {
            if (User::where('username', $username)->get()->count() > 0) {
                $errors['username'] = 'El username introducido ya existe.';
                $haveErrors = true;
            }
        } else {
            $errors['username'] = 'El username introducido no es valido o esta vacio. Debe ingresar un username valido.';
            $haveErrors = true;
        }
        if ($birthday != null && $birthday != '') {
            if (busca_edad($birthday) < 18) {
                $errors['birthday'] = 'Tienes que ser mayor de edad para registrarte.';
                $haveErrors = true;
            }
        } else {
            $errors['birthday'] = 'La fecha de nacimiento no puede estar vacia.';
            $haveErrors = true;
        }
        $edad = busca_edad($birthday);


        if ($haveErrors == false) {
            $user = User::create([
                'name' => $name,
                'last_name' => $last_name,
                'email' => $email,
                'password' => Hash::make($password),
                'username' => $username,
                'birthday' => today(),
                'role' => $role_id,
                'info' => $info,
                'avatar' => 'default.png'

            ]);
            event(new Registered($user));
            $created = true;
        }


        return response()
            ->json([
                'errors' => $errors,
                'created' => $created,
                'haveErrors' => $haveErrors,
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $user_id = $request['user_id'];
        $user = User::Find($user_id);

        $email_new = filter_var(strtolower($request['email_new']), FILTER_SANITIZE_EMAIL);
        $name_new = filter_var($request['name_new'], FILTER_UNSAFE_RAW);
        $last_name_new = filter_var($request['last_name_new'], FILTER_UNSAFE_RAW);
        $username_new = filter_var($request['username_new'], FILTER_UNSAFE_RAW);
        $birthday_new = filter_var($request['birthday_new'], FILTER_UNSAFE_RAW);
        $info_new = filter_var($request['info_new'], FILTER_UNSAFE_RAW);

        $email_old = filter_var(strtolower($request['email_old']), FILTER_SANITIZE_EMAIL);
        $name_old = filter_var($request['name_old'], FILTER_UNSAFE_RAW);
        $last_name_old = filter_var($request['last_name_old'], FILTER_UNSAFE_RAW);
        $username_old = filter_var($request['username_old'], FILTER_UNSAFE_RAW);
        $birthday_old = filter_var($request['birthday_old'], FILTER_UNSAFE_RAW);
        $info_old = filter_var($request['info_old'], FILTER_UNSAFE_RAW);

        $haveErrors = false;
        $updated = false;

        $errors = [
            'email' => '',
            'name' => '',
            'last_name' => '',
            'username' => '',
            'birthday' => '',
        ];



        //validar email
        if ($email_new != '') {
            if (preg_match("/^[A-Za-z0-9_\-\.ñÑ]+\@[A-Za-z0-9_\-\.]+\.[A-Za-z]{2,3}$/", $email_new) && ($email_new != '' && $email_new != null)) {
                if ($email_new != $user->email) {
                    if (User::where('email', $email_new)->get()->count() > 0) {
                        $errors['email'] = 'El email introducido ya existe.';
                        $haveErrors = true;
                    }
                }
            } else {
                $errors['email'] = 'El email introducido no es valido o esta vacio. Debe ingresar un email valido.';
                $haveErrors = true;
            }
        } else {
            $email_new = $email_old;
        }

        //validar name
        if ($name_new == '') {
            $name_new = $name_old;
        }

        //validar username
        if ($username_new != '') {
            if (preg_match("/^[A-Za-z0-9_\-\.ñÑ]{1,15}$/", $username_new) && ($username_new != '' && $username_new != null)) {
                if ($username_new != $user->username) {
                    if (User::where('username', $username_new)->get()->count() > 0) {
                        $errors['username'] = 'El username introducido ya existe.';
                        $haveErrors = true;
                    }
                }
            } else {
                $errors['username'] = 'El username introducido no es valido o esta vacio. Debe ingresar un username valido.';
                $haveErrors = true;
            }
        } else {
            $username_new = $username_old;
        }

        //validar birthday
        if ($birthday_new != null && $birthday_new != '') {
            if (busca_edad($birthday_new) < 18) {
                $errors['birthday'] = 'Tienes que ser mayor de edad para registrarte.';
                $haveErrors = true;
            }
        } else {
            $birthday_new = $birthday_old;
        }

        if ($info_new == '') {
            $info_new = $info_old;
        }

        if ($haveErrors == false) {

            $user->name = $name_new;
            $user->last_name = $last_name_new;
            $user->email = $email_new;
            $user->birthday = $birthday_new;
            $user->username = $username_new;
            $user->info = $info_new;

            $user->save();

            $updated = true;
        }


        return response()
            ->json([
                'errors' => $errors,
                'updated' => $updated,
                'haveErrors' => $haveErrors
            ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $deleted = false;
        $user_id = $request['user_id'];


        if (Auth::user()->role->id != 1) {
            return new Response('Acceso denegado', 200);
        } else if (Auth::user()->role->id == 1) {
            User::find($user_id)->delete();
            $deleted = true;
        }

        return response()
            ->json([
                'deleted' => $deleted
            ]);
    }

    /**
     * Devuelve la foto de perfil del usuario autenticado
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getImage(Request $request)
    {
        $user_id = $request['user_id'];
        $user = User::find($user_id);
        $filename = $user->avatar;
        $file = Storage::disk('users')->get($filename);

        $imageBase64 = base64_encode($file);
        $stringCompleto = "data:image/png;base64,$imageBase64";
        return new Response($stringCompleto, 200);
    }

    /**
     * Devuelve el usuario autenticado
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserAuthId()
    {
        $userAuthId = Auth::user()->id;

        return new Response($userAuthId, 200);
    }

    /**
     * Devuelve el usuario que tenga el id pasado por parametro
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getUserById(Request $request)
    {
        $user_id = $request['user_id'];

        $user = User::find($user_id);


        return response()
            ->json([
                'user' => $user,
                'id' => $user_id
            ]);
    }

    /**
     * Actualiza el avatar del usuario y devuelve si la imagen ha sido actualizada y si ha habido errores
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateAvatar(Request $request)
    {
        $haveErrors = true;
        $updated = false;

        $user = User::find(Auth::user()->id);

        $img = "default.png";
        if ($request->hasFile('image') && $request->file('image') != null && $request->file('image') != '') {
            $file = $request->file('image');
            $img = time() . $file->getClientOriginalName();
            $file->move(storage_path() . '/app/users/', $img);


            $haveErrors = false;

            if ($haveErrors == false) {

                if ($user->avatar != 'default.png') {
                    //Storage::delete($user->avatar);
                    unlink(storage_path('app/users/' . $user->avatar));
                }

                $user->avatar = $img;

                $user->save();

                $updated = true;
            }
        }


        return response()
            ->json([
                'updatedImage' => $updated,
                'haveErrorsImage' => $haveErrors
            ]);
    }

    /**
     * get followers of the user passed in param
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function searchUsers(Request $request)
    {
        $followersAvatars = [];
        $followList = [];
        $result = [];
        $resultFinal = [];
        $text = '';

        $userAuth = User::find(Auth::user()->id);

        $text = $request['text'];

        $result = User::where('name', 'like', '%' . $text . '%')
            ->orWhere('last_name', 'like', '%' . $text . '%')
            ->orWhere('username', 'like', '%' . $text . '%')
            ->get();

        foreach ($result as $key => $value) {
            if ($value->id != $userAuth->id) {
                array_push($resultFinal, $value);
            }
        }

        foreach ($resultFinal as $key => $value) {
            $follow_boolean = false;

            $filename = $value->avatar;
            $file = Storage::disk('users')->get($filename);

            $imageBase64 = base64_encode($file);
            $stringCompletoImage = "data:image/png;base64,$imageBase64";

            $followersAvatars[$value->id] = $stringCompletoImage;


            $seguidor = Follower::where('account_id', $value->id)->where('follower_id', $userAuth->id)->get();
            if (sizeof($seguidor) > 0) {
                $follow_boolean = true;
            }

            $followList[$value->id] = $follow_boolean;
        }


        return response()
            ->json([
                'users' => $resultFinal,
                'avatars' => $followersAvatars,
                'authFollowList' => $followList
            ]);
    }

    /**
     * Devuelve el usuario que tenga el id pasado por parametro
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getAllUsers()
    {
        $users = [];

        if (Auth::user()->role->id != 1) {
            return new Response('Acceso denegado', 200);
        } else if (Auth::user()->role->id == 1) {
            $users = User::all();
        }

        return response()
            ->json([
                'users' => $users
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateById(Request $request)
    {
        if (Auth::user()->role->id != 1) {
            return new Response('Acceso denegado', 200);
        } else if (Auth::user()->role->id == 1) {
            $user_id = $request['user_id'];
            $user = User::Find($user_id);


            $role_id_new = filter_var($request['role_id_new'], FILTER_SANITIZE_NUMBER_INT);
            $email_new = filter_var(strtolower($request['email_new']), FILTER_SANITIZE_EMAIL);
            $name_new = filter_var($request['name_new'], FILTER_UNSAFE_RAW);
            $last_name_new = filter_var($request['last_name_new'], FILTER_UNSAFE_RAW);
            $username_new = filter_var($request['username_new'], FILTER_UNSAFE_RAW);
            $birthday_new = filter_var($request['birthday_new'], FILTER_UNSAFE_RAW);
            $info_new = filter_var($request['info_new'], FILTER_UNSAFE_RAW);

            $email_old = filter_var(strtolower($request['email_old']), FILTER_SANITIZE_EMAIL);
            $name_old = filter_var($request['name_old'], FILTER_UNSAFE_RAW);
            $last_name_old = filter_var($request['last_name_old'], FILTER_UNSAFE_RAW);
            $username_old = filter_var($request['username_old'], FILTER_UNSAFE_RAW);
            $birthday_old = filter_var($request['birthday_old'], FILTER_UNSAFE_RAW);
            $info_old = filter_var($request['info_old'], FILTER_UNSAFE_RAW);

            $haveErrors = false;
            $updated = false;

            $errors = [
                'email' => '',
                'name' => '',
                'last_name' => '',
                'username' => '',
                'birthday' => '',
            ];



            //validar email
            if ($email_new != '' && $email_new != null) {
                if (preg_match("/^[A-Za-z0-9_\-\.ñÑ]+\@[A-Za-z0-9_\-\.]+\.[A-Za-z]{2,3}$/", $email_new) && ($email_new != '' && $email_new != null)) {
                    if ($email_new != $user->email) {
                        if (User::where('email', $email_new)->get()->count() > 0) {
                            $errors['email'] = 'El email introducido ya existe.';
                            $haveErrors = true;
                        }
                    }
                } else {
                    $errors['email'] = 'El email introducido no es valido o esta vacio. Debe ingresar un email valido.';
                    $haveErrors = true;
                }
            } else {
                $email_new = $user->email;
            }

            //validar name
            if ($name_new == '') {
                $name_new = $user->name;
            }

            if ($role_id_new == '' || $role_id_new == null){
                $role_id_new = $user->role->id;
            }

            //validar username
            if ($username_new != '' && $username_new != null) {
                if (preg_match("/^[A-Za-z0-9_\-\.ñÑ]{1,15}$/", $username_new) && ($username_new != '' && $username_new != null)) {
                    if ($username_new != $user->username) {
                        if (User::where('username', $username_new)->get()->count() > 0) {
                            $errors['username'] = 'El username introducido ya existe.';
                            $haveErrors = true;
                        }
                    }
                } else {
                    $errors['username'] = 'El username introducido no es valido o esta vacio. Debe ingresar un username valido.';
                    $haveErrors = true;
                }
            } else {
                $username_new = $user->username;
            }

            //validar birthday
            if ($birthday_new != null && $birthday_new != '') {
                if (busca_edad($birthday_new) < 18) {
                    $errors['birthday'] = 'Tienes que ser mayor de edad para registrarte.';
                    $haveErrors = true;
                }
            } else {
                $birthday_new = $user->birthday;
            }

            if ($haveErrors == false) {

                $user->name = $name_new;
                $user->last_name = $last_name_new;
                $user->email = $email_new;
                $user->birthday = $birthday_new;
                $user->username = $username_new;
                $user->info = $info_new;
                $user->role_id = $role_id_new;

                $user->save();

                $updated = true;
            }
            return response()
                ->json([
                    'errors' => $errors,
                    'updated' => $updated,
                    'haveErrors' => $haveErrors
                ]);
        }
    }


    /**
     * Actualiza el avatar del usuario pasado por parametro y devuelve si la imagen ha sido actualizada y si ha habido errores
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateAvatarByUserId(Request $request)
    {
        $haveErrors = true;
        $updated = false;
        $user_id = $request['user_id'];
        if (Auth::user()->role->id != 1) {
            return new Response('Acceso denegado', 200);
        } else if (Auth::user()->role->id == 1) {

            $user = User::find($user_id);


            $img = "default.png";
            if ($request->hasFile('image') && $request->file('image') != null && $request->file('image') != '') {
                $file = $request->file('image');
                $img = time() . $file->getClientOriginalName();
                $file->move(storage_path() . '/app/users/', $img);


                $haveErrors = false;

                if ($haveErrors == false) {

                    if ($user->avatar != 'default.png') {
                        //Storage::delete($user->avatar);
                        unlink(storage_path('app/users/' . $user->avatar));
                    }

                    $user->avatar = $img;

                    $user->save();

                    $updated = true;
                }
            }
        }

        return response()
            ->json([
                'updatedImage' => $updated,
                'haveErrorsImage' => $haveErrors
            ]);
    }
}
