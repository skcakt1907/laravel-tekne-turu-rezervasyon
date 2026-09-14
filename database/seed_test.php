<?php
use App\Models\{User, CustomerNote, Yacht, Reservation};

$u = User::create(['name'=>'Eski Uye','email'=>'eski@ornek.com','password'=>bcrypt('x'),'role'=>'customer']);
CustomerNote::forceCreate(['user_id'=>$u->id,'admin_id'=>1,'title'=>'Not','body'=>'Eski uyeye ait not']);

$y = Yacht::first();
foreach ([['Ali Veli','ALI@ornek.com','555'],['Ali Veli','ali@ornek.com','5559'],['Eski Uye','eski@ornek.com','444']] as $m) {
    Reservation::forceCreate([
        'code'=>Reservation::generateCode(),'yacht_id'=>$y->id,'owner_id'=>$y->owner_id,
        'customer_name'=>$m[0],'customer_email'=>$m[1],'customer_phone'=>$m[2],'customer_locale'=>'tr',
        'unit'=>'day','starts_at'=>now()->addDays(3),'ends_at'=>now()->addDays(3)->endOfDay(),
        'adults'=>2,'children'=>0,'status'=>'pending','currency'=>'EUR',
    ]);
}
echo "hazir\n";
