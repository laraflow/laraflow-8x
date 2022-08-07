@extends('errors.layout')

@section('title', __('Too Many Requests'))
@section('code', '429')
@section('image', asset('/assets/img/error.png'))
@section('message', __('Too Many Requests'))
