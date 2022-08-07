@extends('errors.layout')

@section('title', __('Service Unavailable'))
@section('code', '503')
@section('image', asset('/assets/img/error.png'))
@section('message', __('Service Unavailable'))
