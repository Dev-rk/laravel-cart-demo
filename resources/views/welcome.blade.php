@extends('layouts.main')

@section('content')
    <div class="container">

        @foreach($products as $product)
            <div class="col-sm-6">
                <h4>{{$product->name}}</h4>
                <p class="col-12">Price: <span class="text-success">{{$product->price}}</span></p>
            </div>
        @endforeach
    </div>
@endsection