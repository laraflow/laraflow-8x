@extends('errors.layout')

@section('title', __('Server Error'))
@section('code', '500')
@section('image', asset('/assets/img/error.png'))
@section('message', __('Server Error'))
