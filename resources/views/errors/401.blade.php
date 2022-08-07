@extends('errors.layout')

@section('title', __('Unauthorized'))
@section('code', '401')
@section('image', asset('/assets/img/error.png'))
@section('message', __('Unauthorized'))
