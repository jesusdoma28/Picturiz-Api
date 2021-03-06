<?php

namespace App\Http\Controllers;

use App\Models\Follower;
use App\Models\Like;
use App\Models\User;
use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;

use function PHPSTORM_META\map;
use function Psy\debug;

class PublicationController extends Controller
{
    /**
     * Muestra la feed del usuario.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $lista = [];
        $likesList = [];
        $likes = [];
        $meGusta = [];
        $usersList = [];
        $imagesList = [];
        $commentsList = [];
        $userCommentsList = [];
        $listaIdsUserComments = [];
        $userCommentsListMap = [];
        $avatarsList = [];

        $seguidos = Follower::where('follower_id', Auth::user()->id)->get();
        foreach ($seguidos as $key => $value) {
            array_push($lista, (string)$value->account_id);
            debug($lista);
        }

        $publications =  Publication::orderBy('created_at', 'DESC')->whereIn('user_id', $lista)->take(40)->get();
        foreach ($publications as $key => $value) {
            $listaIdsUserComments = [];
            $user = User::find($value->user_id);


            $filename = $user->avatar;
            $file = Storage::disk('users')->get($filename);

            $imageBase64 = base64_encode($file);
            $stringCompletoImage = "data:image/png;base64,$imageBase64";

            $avatarsList[$value->id] = $stringCompletoImage;


            $meGustaBool = false;
            $likesList[$value->id] = Publication::find($value->id)->likes;
            $likes[$value->id] = sizeof($likesList[$value->id]);

            $publiComments = Publication::find($value->id)->comments;

            $usersList[$value->user_id] = $user;

            foreach ($likesList[$value->id] as $keyL => $valueL) {
                if (($meGustaBool == false) && ($valueL->user_id == Auth::user()->id)) {
                    $meGustaBool = true;
                }
            }

            $filename = $value->img;
            $file = Storage::disk('publications')->get($filename);

            $imageBase64 = base64_encode($file);
            $stringCompletoImage = "data:image/png;base64,$imageBase64";

            $imagesList[$value->id] = $stringCompletoImage;

            $meGusta[$value->id] = $meGustaBool;

            foreach ($publiComments as $keypc => $valuepc) {
                array_push($listaIdsUserComments, $valuepc->user_id);
            }
            foreach ($listaIdsUserComments as $keyliuc => $valueliuc) {
                $userCommentsListMap[$valueliuc] = User::find($valueliuc);
            }
            $userCommentsList[$value->id] = $userCommentsListMap;

            $commentsList[$value->id] = $publiComments;
        }

        return response()
            ->json([
                'publis' => $publications,
                'likes' => $likes,
                'meGusta' => $meGusta,
                'users' => $usersList,
                'images' => $imagesList,
                'comments' => $commentsList,
                'userComments' => $userCommentsList,
                'avatars' => $avatarsList
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $haveErrors = true;
        $uploaded = false;

        $img = "default.png";
        $user_id = $request['user_id'];
        if ($request->hasFile('image') && $request->file('image') != null && $request->file('image') != '' && $user_id != null && $user_id != '') {
            $file = $request->file('image');
            $img = time() . $file->getClientOriginalName();
            $file->move(storage_path() . '/app/publications/', $img);

            $description = filter_var($request['description'],FILTER_UNSAFE_RAW);


            $haveErrors = false;

            if ($haveErrors == false) {
                $publication = Publication::create([
                    'img' => $img,
                    'description' => $description,
                    'visible' => 1,
                    'user_id' => $user_id
                ]);

                $publication->save();

                $uploaded = true;
            }
        }


        return response()
            ->json([
                'uploaded' => $uploaded
            ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $deleted = false;
        $publication_id = $request['publication_id'];

        if ($publication_id != '' && $publication_id != null) {
            $publication = Publication::find($publication_id);
            if ($publication->img != 'example1.png' && $publication->img != 'example2.png' && $publication->img != 'example3.png') {
                unlink(storage_path('app/publications/' . $publication->img));
            }
            $publication->delete();

            $deleted = true;
        }

        return response()
            ->json([
                'deleted' => $deleted
            ]);
    }

    /**
     * Get Publications of one user passed in param.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getPublicationsAndImagesOfUserByUserId(Request $request)
    {
        $user_id = null;
        $imagesList = [];
        $user_id = $request['user_id'];

        $publications = Publication::where('user_id', $user_id)->get();
        foreach ($publications as $key => $value) {
            $filename = $value->img;
            $file = Storage::disk('publications')->get($filename);

            $imageBase64 = base64_encode($file);
            $stringCompletoImage = "data:image/png;base64,$imageBase64";

            $imagesList[$value->id] = $stringCompletoImage;
        }
        if ($user_id == null || $user_id == '') {
            return new Response('id null or empty', 400);
        } else {
            return response()
                ->json([
                    'publications' => $publications,
                    'images' => $imagesList
                ]);
        }
    }

    /**
     * Get Publication and image with id passed in param.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getPublicationsAndImagesById(Request $request)
    {
        $publication_id = $request['publication_id'];

        $publication = Publication::find($publication_id);

        $filename = $publication->img;
        $file = Storage::disk('publications')->get($filename);

        $imageBase64 = base64_encode($file);
        $stringCompletoImage = "data:image/png;base64,$imageBase64";

        $image = $stringCompletoImage;

        if ($publication_id == null || $publication_id == '') {
            return new Response('id null or empty', 400);
        } else {
            return response()
                ->json([
                    'publication' => $publication,
                    'image' => $image
                ]);
        }
    }

    /**
     * Get Likes and if the autenticated user liked the publications.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getLikesOfPublicationById(Request $request)
    {
        $meGustaBool = false;
        $publication_id = $request['publication_id'];

        $likes = Like::where('publication_id', $publication_id)->get();

        foreach ($likes as $key => $value) {
            if (($meGustaBool == false) && ($value->user_id == Auth::user()->id)) {
                $meGustaBool = true;
            }
        }

        if ($publication_id == null || $publication_id == '') {
            return new Response('id null or empty', 400);
        } else {
            return response()
                ->json([
                    'likes' => sizeof($likes),
                    'meGusta' => $meGustaBool
                ]);
        }
    }


    /**
     * Get Comments and users of a publication.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getCommentsAndUsersByPublicationId(Request $request)
    {
        $publication_id = $request['publication_id'];
        $userCommentsList = [];
        $publiComments = [];
        $listaIdsUserComments = [];

        $publiComments = Publication::find($publication_id)->comments;
        foreach ($publiComments as $keypc => $valuepc) {
            array_push($listaIdsUserComments, $valuepc->user_id);
        }
        foreach ($listaIdsUserComments as $keyliuc => $valueliuc) {
            $userCommentsList[$valueliuc] = User::find($valueliuc);
        }

        if ($publication_id == null || $publication_id == '') {
            return new Response('id null or empty', 400);
        } else {
            return response()
                ->json([
                    'comments' => $publiComments,
                    'userComments' => $userCommentsList
                ]);
        }
    }
}
