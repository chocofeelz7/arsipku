<?php

namespace App\Http\Controllers;

use App\Models\{Activity, Document, DocumentFile, Division, InboxMessage};
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $base = Document::visibleTo($request->user());
        $counts = ['total' => (clone $base)->count(), 'active' => (clone $base)->whereNotNull('active_version_id')->count()];
        foreach (['draft', 'submitted', 'revision', 'rejected'] as $status) {
            $counts[$status] = (clone $base)->whereHas('latestVersion', fn ($q) => $q->where('status', $status))->count();
        }
        $activities = Activity::whereHas('document', fn ($q) => $q->visibleTo($request->user()))->with(['user', 'document'])->latest()->limit(7)->get();
        $recent = (clone $base)->with(['latestVersion.category', 'division'])->latest()->limit(6)->get();
        $divisions = $request->user()->is_admin ? Division::all()->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'code' => $d->code, 'count' => Document::where('division_id', $d->id)->count()]) : collect();
        $storageBytes = $request->user()->is_admin ? DocumentFile::select('path', 'size')->distinct()->get()->sum('size') : null;
        return view('dashboard', compact('counts', 'activities', 'recent', 'divisions', 'storageBytes'));
    }

    public function activities(Request $request)
    {
        $query = Activity::with(['user', 'document'])->latest();
        if (! $request->user()->is_admin) { $query->whereHas('document', fn ($q) => $q->visibleTo($request->user())); }
        return view('activities', ['activities' => $query->paginate(25)]);
    }

    public function inbox(Request $request)
    {
        return view('inbox', ['messages' => InboxMessage::where('user_id', $request->user()->id)->whereHas('document', fn ($q) => $q->visibleTo($request->user()))->with('document')->latest()->paginate(20)]);
    }

    public function read(Request $request, InboxMessage $message)
    {
        abort_unless($message->user_id === $request->user()->id && $request->user()->canAccess($message->document), 403);
        $message->update(['read_at' => now()]);
        return redirect()->route('documents.show', $message->document_id);
    }
}
