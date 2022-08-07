@extends('errors.layout')

@section('title', __('Not Found'))
@section('code', '404')
@section('image', asset('/assets/img/error.png'))
@section('message', __('Not Found'))
