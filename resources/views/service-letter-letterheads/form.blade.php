@extends('layouts.app')
@section('title',$letterhead->exists?'Edit Letterhead':'New Letterhead')
@section('content')
<h2 class="md-headline-sm">🏛 {{ $letterhead->exists?'Edit':'Create' }} Service Letter Letterhead</h2>
<p class="md-body-sm" style="margin-bottom:16px">Used on the browser-print version of approved letters. Keep contact and institutional wording exactly as officially approved.</p>
<form class="md-card md-card--elevated" method="POST" enctype="multipart/form-data" action="{{ $letterhead->exists?route('service-letter-letterheads.update',$letterhead):route('service-letter-letterheads.store') }}">@csrf @if($letterhead->exists)@method('PUT')@endif
<div class="md-card__body"><div class="md-form-row">
@foreach(['name'=>'Profile name','institution_name'=>'Institution name','ministry_name'=>'Ministry','department_name'=>'Government / Department','address_line_1'=>'Address line 1','address_line_2'=>'Address line 2','telephone'=>'Telephone','fax'=>'Fax','email'=>'Email','website'=>'Website','reference_prefix'=>'Reference prefix','signatory_designation'=>'Signatory designation'] as $field=>$label)
<div class="md-form-group"><label class="md-label">{{ $label }} @if(in_array($field,['name','institution_name']))* @endif</label><input class="md-input" name="{{ $field }}" value="{{ old($field,$letterhead->{$field}) }}" @if(in_array($field,['name','institution_name'])) required @endif></div>
@endforeach
</div><div class="md-form-group"><label class="md-label">Header logo / emblem image (optional)</label><input class="md-input" type="file" name="logo" accept="image/png,image/jpeg,image/webp"><div class="md-field-hint">Maximum 2 MB. Stored locally on the hospital server.</div></div><div class="md-form-row"><div class="md-form-group"><label class="md-label">Header note</label><textarea class="md-input" name="header_note" rows="3">{{ old('header_note',$letterhead->header_note) }}</textarea></div><div class="md-form-group"><label class="md-label">Footer note</label><textarea class="md-input" name="footer_note" rows="3">{{ old('footer_note',$letterhead->footer_note) }}</textarea></div></div>
<label style="display:flex;gap:8px;margin:10px 0"><input type="checkbox" name="show_national_emblem" value="1" {{ old('show_national_emblem',$letterhead->show_national_emblem??true)?'checked':'' }}> Show national emblem placeholder</label>
<label style="display:flex;gap:8px;margin:10px 0"><input type="checkbox" name="is_default" value="1" {{ old('is_default',$letterhead->is_default)?'checked':'' }}> Make default letterhead</label>
<label style="display:flex;gap:8px;margin:10px 0"><input type="checkbox" name="is_active" value="1" {{ old('is_active',$letterhead->exists?$letterhead->is_active:true)?'checked':'' }}> Active</label>
</div><div class="md-card__footer"><a class="md-btn md-btn--outlined" href="{{ route('service-letter-letterheads.index') }}">Cancel</a><button class="md-btn md-btn--filled">Save Letterhead</button></div></form>
@endsection
