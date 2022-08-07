@extends('errors.layout')

@section('title', __('Forbidden'))
@section('code', '403')
@section('image', asset('/assets/img/error.png'))
@section('message', __($exception->getMessage() ?: 'Forbidden'))
