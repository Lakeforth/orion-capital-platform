<div
  class="col-span-12 rounded-sm border border-stroke bg-white px-5 pt-7.5 pb-5 shadow-default dark:border-strokedark dark:bg-boxdark sm:px-7.5 xl:col-span-8">
  <div class="flex flex-wrap items-start justify-between gap-3 sm:flex-nowrap">
    <div class="flex w-full flex-wrap gap-3 sm:gap-5">
      <div class="flex min-w-47.5">
        <span class="mt-1 mr-2 flex h-4 w-full max-w-4 items-center justify-center rounded-full border border-green-700">
          <span class="block h-2.5 w-full max-w-2.5 rounded-full bg-green-500"></span>
        </span>
        <div class="w-full">
          <p class="font-semibold text-green-500">Total Revenue</p>
          <p class="text-sm font-medium"> {{ show_chart_date(request()->get('data')) }}</p>
        </div>
      </div>
    </div>

  </div>
  <div>


              <x-splade-data :default="$chart1">
                  <apexchart
                      :width="data.width"
                      :height="data.height"
                      :type="data.type"
                      :options="data.options"
                      :series="data.series"
                  />
              </x-splade-data>

  </div>
</div>
