@extends('layouts.bare')

@section('title', 'API explorer — azshrtr')
@section('meta_description', 'Interactive Azshrtr OpenAPI explorer powered by Stoplight Elements.')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/@stoplight/elements@8.5.2/styles.min.css">
    <style>
        html, body {
            margin: 0;
            height: 100%;
            overflow: hidden;
        }
        elements-api {
            display: block;
            height: 100vh;
            width: 100vw;
        }
    </style>
@endpush

@section('content')
    <elements-api
        apiDescriptionUrl="{{ asset('openapi.yaml') }}"
        router="hash"
        layout="sidebar"
        tryItCredentialsPolicy="include"
    ></elements-api>
    <script src="https://unpkg.com/@stoplight/elements@8.5.2/web-components.min.js"></script>
@endsection
