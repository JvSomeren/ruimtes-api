<?php

namespace App\Http\Controllers;

use App\Admin;
use App\Auth;
use App\Session;
use Genkgo\Api\Connection;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuthController extends Controller
{
    private function add_to_response(&$obj, $key, $val)
    {
        $obj = (array)$obj;
        $obj[$key] = $val;
        $obj = (object)$obj;
    }

    private function convert_epoch_to_datetime($epoch)
    {
        return date('Y-m-d H:i:s', substr($epoch, 0, 10));
    }

    private function validateWithGenkgo(Request $request)
    {
        try
        {
            $conn = new Connection(new Client(), env('GENKGO_URL'), env('GENKGO_TOKEN'));

            $res = $conn->command('organization', 'login', [
                'uid' => $request->uid,
                'password' => $request->password,
                'returnType' => 'entry'
            ]);

            return $res->getBody();
        } catch(\GuzzleHttp\Exception\RequestException $e)
        {
            $err = $e->getMessage();
            if(strpos($err, 'Wrong login credentials') !== false) {
                return ['msg' => 'Verkeerde login gegevens'];
            }

            return $err;
        }
    }

    private function clean_genkgo_response(&$obj)
    {
        unset($obj->id);
        unset($obj->type);
        unset($obj->cssclass);
        unset($obj->parentid);
        unset($obj->dn);
        unset($obj->lastmodified);
        unset($obj->selfmodified);
        unset($obj->system);
        unset($obj->collection);
    }

    private function create_hash($uid, $password)
    {
        $nonce = uniqid();
        $hash1 = password_hash($uid . ':' . $password, PASSWORD_BCRYPT);
        $hash2 = password_hash($_SERVER['REQUEST_METHOD'] . ':' . $_SERVER['REQUEST_URI'], PASSWORD_BCRYPT);
        $hash = password_hash($hash1 . ':' . $nonce . ':' . $hash2, PASSWORD_BCRYPT);

        return $hash;
    }

    private function add_admin($uid)
    {
        Admin::firstOrCreate(['uid' => $uid]);
    }

    private function delete_admin($uid)
    {
        Admin::where('uid', $uid)->delete();
    }

    private function is_admin($uid)
    {
        try
        {
            Admin::where('uid', $uid)->firstOrFail();

            return true;
        } catch(ModelNotFoundException $e)
        {
            return false;
        }
    }

    private function create_session($uid, $hash, $expires)
    {
        // create session entry
        $session = new Session;
        $session->uid = $uid;
        $session->hash = $hash;
        $session->expires = $expires;

        $session->save();
    }

    private function validate_session($uid, $hash)
    {
        try
        {
            $time = $this->convert_epoch_to_datetime(time());

            Session::where('uid', $uid)
                ->where('hash', $hash)
                ->whereDate('expires', '>', $time)
                ->firstOrFail();

            return true;
        } catch(ModelNotFoundException $e)
        {
            return false;
        }
    }

    public function addAdmin(Request $request)
    {
        $this->validate($request, [
            'uid'       => 'required',
            'hash'      => 'required',
            'adminUid'  => 'required'
        ]);

        if($this->validate_session($request->uid, $request->hash) &&
            $this->is_admin($request->uid))
        {
            $this->add_admin($request->adminUid);

            return response()->json([
                'msg' => 'Beheerder toegevoegd',
                'body' => $request->adminUid
            ], 200);
        } else
        {
            return response()->json(['msg' => 'Missende rechten'], 401);
        }
    }

    public function clearSessions(Request $request)
    {
        $this->validate($request, [
            'uid'       => 'required',
            'hash'      => 'required',
        ]);

        if($this->validate_session($request->uid, $request->hash))
        {
            $time = $this->convert_epoch_to_datetime(time()-1);

            Session::where('uid', $request->uid)
                ->whereDate('expires', '>', $time)
                ->update(['expires' => $time]);

            return response()->json(['msg' => 'Sessions cleared'], 200);
        } else
        {
            return response()->json(['msg' => 'Invalide sessie'], 401);
        }
    }

    public function deleteAdmin(Request $request)
    {
        $this->validate($request, [
            'uid'       => 'required',
            'hash'      => 'required',
            'adminUid'  => 'required'
        ]);

        if($this->validate_session($request->uid, $request->hash) &&
            $this->is_admin($request->uid))
        {
            $this->delete_admin($request->adminUid);

            return response()->json([
                'msg' => 'Beheerder verwijderd',
                'body' => $request->adminUid
            ], 200);
        } else
        {
            return response()->json(['msg' => 'Missende rechten'], 401);
        }
    }

    public function isAdmin(Request $request)
    {
        $this->validate($request, [
            'uid'       => 'required',
            'hash'      => 'required'
        ]);

        if($this->validate_session($request->uid, $request->hash))
        {
            $is_admin = $this->is_admin($request->uid);

            if($is_admin)
                return response()->json([
                    'msg' => 'Gebruiker is beheerder',
                    'isAdmin' => $is_admin
                ], 200);
            else
                return response()->json([
                    'msg' => 'Gebruiker is geen beheerder',
                    'isAdmin' => $is_admin
                ], 200);
        } else
        {
            return response()->json(['msg' => 'Invalide sessie'], 401);
        }
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'uid'       => 'required',
            'password'  => 'required'
        ]);

        $res = $this->validateWithGenkgo($request);

        if(isset($res->name))
        {
            // cleanup response
            $this->clean_genkgo_response($res);
            // create hash
            $this->add_to_response(
                $res,
                'hash',
                $this->create_hash($request->uid, $request->password)
            );
            // set expiry date
            $this->add_to_response(
                $res,
                'expires',
                time()+60*60*24*14
            );
            // set admin parameter
            $this->add_to_response(
                $res,
                'isAdmin',
                $this->is_admin($request->uid)
            );
            // add session to db
            $this->create_session(
                $request->uid,
                $res->hash,
                $this->convert_epoch_to_datetime($res->expires)
            );

            return response()->json($res, 200);
        }
        else 
            return response()->json($res, 401);

    }

    public function logout(Request $request)
    {
        $this->validate($request, [
            'uid'       => 'required',
            'hash'      => 'required',
        ]);

        if($this->validate_session($request->uid, $request->hash))
        {
            $time = $this->convert_epoch_to_datetime(time()-1);

            Session::where('uid', $request->uid)
                ->where('hash', $request->hash)
                ->update(['expires' => $time]);

            return response()->json(['msg' => 'Logged out'], 200);
        } else
        {
            return response()->json(['msg' => 'Invalide sessie'], 401);
        }
    }

    public function validateCookies(Request $request)
    {
        $this->validate($request, [
            'uid'       => 'required',
            'hash'      => 'required',
        ]);

        if($this->validate_session($request->uid, $request->hash))
        {
            return response()->json(['msg' => 'Valide cookies'], 200);
        } else
        {
            return response()->json(['msg' => 'Invalide sessie'], 401);
        }
    }
}