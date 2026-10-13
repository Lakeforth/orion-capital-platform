<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Tables\ProviderDeliveryFeeTable;
use App\Tables\ProviderProductTable;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\Facades\Toast;

class ProviderController extends Controller
{
    public function myServices(){
//        $services = Service::whereNot('type', 'utility')->get();

        if(!auth()->user()->vendor){
            Toast::warning('You are not a vendor');
            return redirect()->route('admin.dashboard');
        }


        if(is_vendor()){
            if(vendor_is_incomplete_setup()){
                return redirect()->route('admin.vendors.edit', vendor_id());
            }
        }


        $services = auth()->user()->vendor->services()->whereNotIn('type',['waste','car_wash','cleaning'])->withCount('my_products')->get();

        $vendor_id = vendor_id();

        return view('admin.provider.my_services', compact('services','vendor_id'));
    }

    public function myStore(){
        $vendor = auth()->user()->vendor;
        if(!$vendor){
            return 'ddd';
        }

        return view('admin.vendors.show', compact('vendor'));
    }

    public function serviceListings($id, Request $request){
        $vendor_id = $request->get('vendor_id');
        $service = Service::select('name','id','type')->where('id', $id)->firstOrFail();

        if($service->type == 'delivery'){
            $data = new ProviderDeliveryFeeTable($service->id);
            $default = [
                'price_1' => 500,
                'price_2' => 1000,
                'price_3' => 1500,
                'price_4' => 2000,
                'price_5' => 2500,
                'price_6' => 3500,
                'price_7' => 4000,
                'service_id' => $service->id
            ];
        }else{
            $data = new ProviderProductTable($service->id, $vendor_id);
            $default = [];
        }
        return view('admin.products.listings', [
            'data' => $data,
            'title' => $service->type,
            'service' => $service,
            'id' => $id,
            'default' => $default,
        ]);

    }
}
