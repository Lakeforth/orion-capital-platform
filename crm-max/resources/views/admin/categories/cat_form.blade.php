
<x-splade-select  label="Parent Category" placeholder="No parent" name="parent_id" choices remote-url="{{ route('api.categories.index') }}?service_id={{ $service_id }}"
                  option-label="name" option-value="id" />

<x-splade-input required label="Name" name="name" />

<x-splade-input type="hidden" name="service_id" />


<x-splade-textarea label="Description" name="description" autosize />


<x-splade-file filepond
               name="image" preview label="Image"
               min-size="10KB" max-size="5MB" />
