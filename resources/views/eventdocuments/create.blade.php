@extends('layouts.app')

@section('content')
<div class="container">
    <form action="{{route('event_documents.store',['event_slug' => $event->slug,])}}" enctype="multipart/form-data" method="POST" id="eventDocumentForm">
        @csrf
        <div class="row">
            <div class="col-8 offset-2">
                <h2 class="text-center">New Event Document</h2>
                <h6 class="text-center">{{ $event->title }}</h6>
                <div class="form-group row">
                    <label for="title" class="col-md-4 col-form-label">Title<span style="color:red">*</span></label>
                    <input id="title" 
                    type="text" 
                    class="form-control @error('title') is-invalid @enderror" 
                    name="title"
                    placeholder="Document Title" 
                    value="{{ old('title') }}" 
                    autofocus 
                    required>
                    @error('title')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="form-group row">
                    <label for="description" class="col-md-4 col-form-label">Description</label>    
                    <textarea id="description" 
                    class="form-control @error('description') is-invalid @enderror" 
                    name="description"
                    placeholder="Document Description...">{{ old('description')}}</textarea>
                    @error('description')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="form-group row">
                    <label for="document_type" class="form-label">Select Document Type<span style="color:red">*</span></label>
                    <select id="document_type" name="document_type"class="form-control" required>
                        <option value="" selected>Select a document type...</option>
                        @foreach($eventDocumentTypes as $documentType)
                        <option value="{{$documentType->event_document_type_id}}">{{$documentType->document_type}}</option>
                        @endforeach
                    </select>
                    @error('document_type')
                    <div class="row">
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    </div>
                    @enderror
                </div>
                
                <input type="file" 
                        class="filepond @error('document') is-invalid @enderror"
                        name="document" 
                        id="document"
                        accept="application/pdf"
                        data-max-file-size="3MB"
                        required>
                @error('document')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
                <hr>
                <div class="row pt-4">
                    <button class="btn btn-primary">Add Document</button>
                </div>
            </div>
        </div>
    </form>
    <hr>
    <div class="row justify-content-center">
        <a href="/home">
            <button class="btn btn-secondary">Go back</button>
        </a>
    </div>
</div>
@endsection
@if($filePondJS ?? false)
    @push('scripts')
        {{-- FilePond CSS --}}
        <link href="{{ asset('css/filepond.css') }}" rel="stylesheet">      
    @endpush

    @push('footer-scripts')
        {{-- FilePond JS --}}
        <script src="https://unpkg.com/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.js"></script>
        <script src="https://unpkg.com/filepond-plugin-file-validate-size/dist/filepond-plugin-file-validate-size.js"></script>
        <script src="{{ asset('js/filepond.js') }}" type="text/javascript"></script>
    @endpush
@endif

@section('scripts')
    <script type="module">
        FilePond.registerPlugin(FilePondPluginFileValidateType);
        FilePond.registerPlugin(FilePondPluginFileValidateSize);
        const mainInputElement = document.querySelectorAll('input.filepond');
        // Create a FilePond instance
        Array.from(mainInputElement).forEach(inputElement => {
          // create a FilePond instance at the input element location
          FilePond.create( inputElement, {
            maxFileSize: '3MB',
            acceptedFileTypes: ['application/pdf'],
            labelFileTypeNotAllowed: 'Only PDF Documents are allowed.',
            labelIdle: 'Drop a PDF here or <span class="filepond--label-action"> Browse </span>',
            });

        });

        FilePond.setOptions({
            server: {
                url: '/e/documents/upload',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                revert: '/revert',
            }
        });
    </script>
@endsection
