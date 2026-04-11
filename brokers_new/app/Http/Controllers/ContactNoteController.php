<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\ContactNoteRequest;
use App\ContactNote;


class ContactNoteController extends Controller
{
    
    public function store(ContactNoteRequest $request){
        
        $contact = new ContactNote;
        $contact->content = $request->content;
        $contact->contact_id = $request->contact_id;
        $contact->type = 1;
        $contact->user_id = auth()->user()->id;
        $contact->save();
        return back();
        
    }
}
