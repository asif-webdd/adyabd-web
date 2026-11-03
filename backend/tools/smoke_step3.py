import json, urllib.request, urllib.error, subprocess, random, time
B='http://localhost:8000'
def call(m,path,body=None,tok=None):
    h={'Accept':'application/json','Content-Type':'application/json'}
    if tok:h['Authorization']='Bearer '+tok
    r=urllib.request.Request(B+path,data=json.dumps(body).encode() if body is not None else None,headers=h,method=m)
    try: resp=urllib.request.urlopen(r); code=resp.status; txt=resp.read().decode()
    except urllib.error.HTTPError as e: code=e.code; txt=e.read().decode()
    try:return code,json.loads(txt)
    except:return code,txt[:300]
def ok(n,c,extra=''): print(('PASS ' if c else 'FAIL ')+n+('' if c else ' '+str(extra)[:300]))
DB='database/database.sqlite'
def sql(q):
    import sqlite3; c=sqlite3.connect(DB); c.row_factory=sqlite3.Row; cur=c.execute(q); r=[dict(x) for x in cur.fetchall()] if q.strip().lower().startswith('select') else None; c.commit(); c.close(); return r

phone='018'+str(random.randint(10000000,99999999))
c,d=call('POST','/api/v1/auth/register',{'name':'Seller Test','phone':phone,'password':'Passw0rd!x'}); ok('register new user',c in(200,201),d); tok=d['data']['token']
# --- seller flow
c,d=call('GET','/api/v1/seller/dashboard',tok=tok); ok('non-seller blocked (403 NOT_A_SELLER)',c==403 and d.get('code')=='NOT_A_SELLER',(c,d))
good={'shop_name':'Test Shop '+phone[-4:],'owner_name':'Owner','phone':phone,'address':'Dhaka','payment_method':'bkash','mfs_number':phone,'agreed':True,'business_type':'retail','category':'ফ্যাশন','monthly_sales':'<100k'}
c,d=call('POST','/api/v1/seller/register',{**good,'phone':'123'},tok); ok('bad phone rejected (422)',c==422,(c,d))
c,d=call('POST','/api/v1/seller/register',{**good,'agreed':False},tok); ok('terms must be accepted (422)',c==422,(c,d))
c,d=call('POST','/api/v1/seller/register',good,tok); ok('seller register -> 201 pending',c==201 and d['data']['status']=='pending',(c,d))
c,d=call('POST','/api/v1/seller/register',good,tok); ok('second application refused (422)',c==422,(c,d))
c,d=call('GET','/api/v1/seller/dashboard',tok=tok); ok('pending seller blocked (SELLER_PENDING)',c==403 and d.get('code')=='SELLER_PENDING',(c,d))
slug=sql("select slug from sellers order by id desc limit 1")[0]['slug']
c,d=call('GET','/api/v1/sellers/'+slug); ok('pending seller hidden publicly (404)',c==404,(c,d))
sql("update sellers set status='active' where slug='%s'"%slug)
c,d=call('GET','/api/v1/sellers/'+slug); ok('active seller public page 200',c==200,(c,d))
c,d=call('GET','/api/v1/seller/dashboard',tok=tok); ok('active seller dashboard 200',c==200 and 'shop_name' in d['data'],(c,d))
c,d=call('POST','/api/v1/seller/products',{'name':'Hack Product','category_id':1,'price':100,'quantity':5,'is_featured':1,'is_active':1,'seller_id':1},tok)
ok('product created, mass-assignment ignored',c==201 and d['data']['is_active'] in(False,0) and not d['data'].get('is_featured') and d['data']['seller_id']!=1,(c,d))
c,d=call('PUT','/api/v1/seller/products/%s'%d['data']['id'],{'is_active':True},tok); c2,p=call('GET','/api/v1/seller/products',tok=tok)
ok('seller cannot self-approve product',not p['data']['data'][0]['is_active'],p)
# --- follow
c,d=call('POST','/api/v1/auth/login',{'phone':'01711000001','password':'Password@123'}); ut=d['data']['token']
c,d=call('POST','/api/v1/sellers/%s/follow'%slug,tok=ut); ok('follow',c==200 and d['data']['is_following'] is True and d['data']['followers_count']==1,(c,d))
c,d=call('GET','/api/v1/sellers/'+slug,tok=ut); ok('is_following reflected in public page',d['data']['is_following'] is True,d)
c,d=call('POST','/api/v1/sellers/%s/follow'%slug,tok=ut); ok('unfollow',d['data']['is_following'] is False,d)
c,d=call('POST','/api/v1/sellers/%s/follow'%slug); ok('follow needs login (401)',c==401,(c,d))
# --- contact
c,d=call('POST','/api/v1/contact',{'name':'Rahim','phone':'01711223344','subject':'অর্ডার সম্পর্কিত','message':'আমার অর্ডার এখনো আসেনি, দয়া করে দেখুন।'}); ok('contact message stored',c==200 and sql("select count(*) n from contact_messages")[0]['n']==1,(c,d))
c,d=call('POST','/api/v1/contact',{'name':'x','phone':'abc','subject':'s','message':'short'}); ok('contact validation (422)',c==422,(c,d))
# --- returns
uid=sql("select id from users where phone='01711000001'")[0]['id']
if not sql("select id from orders where user_id=%d limit 1"%uid): sql("update orders set user_id=%d where id=(select min(id) from orders)"%uid)
oid=sql("select id from orders where user_id=%d limit 1"%uid)[0]['id']
sql("update orders set status='delivered', delivered_at=datetime('now') where id=%d"%oid)
c,d=call('GET','/api/v1/returns/eligible-orders',tok=ut); ok('eligible orders works (model/relation exist now)',c==200 and isinstance(d,list) and len(d)>0,(c,str(d)[:200]))
c,d=call('POST','/api/v1/returns',{'order_id':oid,'type':'return','reason':'Damaged','description':'The product arrived damaged, please help me.','refund_method':'wallet'},ut); ok('return request created (201)',c==201,(c,d))
c,d=call('POST','/api/v1/returns',{'order_id':oid,'type':'return','reason':'Damaged','description':'The product arrived damaged, please help me.','refund_method':'wallet'},ut); ok('duplicate return refused',c==422,(c,d))
c,d=call('POST','/api/v1/orders/%d/return'%oid,{'reason':'again'},ut); ok('legacy return endpoint also deduped',c==422,(c,d))
c,d=call('GET','/api/v1/returns',tok=ut); ok('list my returns',c==200 and len(d['data'])==1,(c,str(d)[:200]))
# --- social login (not configured)
c,d=call('POST','/api/v1/auth/social-login',{'provider':'google','token':'x'}); ok('google login not configured -> 501',c==501,(c,d))
c,d=call('POST','/api/v1/auth/social-login',{'provider':'facebook','token':'x'}); ok('unsupported provider -> 422',c==422,(c,d))
# --- wallet withdraw
c,me=call('GET','/api/v1/auth/me',tok=ut); w0=float(me['data']['wallet_balance']); ok('me includes seller key',('seller' in me['data']),list(me['data'].keys()))
c,d=call('POST','/api/v1/user/wallet/withdraw',{'amount':100,'method':'bkash','account_number':'01711000001'},ut); ok('withdraw 100',c==200,(c,d))
c,me=call('GET','/api/v1/auth/me',tok=ut); ok('balance reduced',abs(float(me['data']['wallet_balance'])-(w0-100))<0.01,me['data']['wallet_balance'])
row=sql("select * from transactions where payment_method='bkash' order by id desc limit 1")[0]; ok('ledger has balance_before/after',abs(row['balance_before']-w0)<0.01 and abs(row['balance_after']-(w0-100))<0.01,row)
c,d=call('POST','/api/v1/user/wallet/withdraw',{'amount':99999,'method':'bkash','account_number':'01711000001'},ut); ok('over-balance refused (422)',c==422,(c,d))
# --- delete account then re-register same phone
c,d=call('DELETE','/api/v1/user/account',tok=tok); ok('delete account',c==200,(c,d))
time.sleep(1)
c,d=call('POST','/api/v1/auth/register',{'name':'Again','phone':phone,'password':'Passw0rd!x'}); ok('same phone can register again after deletion',c in(200,201),(c,d))
