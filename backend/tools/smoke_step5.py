# Multi-vendor checkout checks. Run from backend/ with the server on :8000 (python3 tools/smoke_step5.py)
import json, urllib.request, urllib.error, sqlite3
B='http://localhost:8000'; DB='database/database.sqlite'
def call(m,path,body=None,tok=None):
    h={'Accept':'application/json','Content-Type':'application/json'}
    if tok:h['Authorization']='Bearer '+tok
    r=urllib.request.Request(B+path,data=json.dumps(body).encode() if body is not None else None,headers=h,method=m)
    try: resp=urllib.request.urlopen(r); code=resp.status; txt=resp.read().decode()
    except urllib.error.HTTPError as e: code=e.code; txt=e.read().decode()
    try:return code,json.loads(txt)
    except:return code,txt[:300]
def sql(q,*a):
    c=sqlite3.connect(DB); c.row_factory=sqlite3.Row; cur=c.execute(q,a)
    r=[dict(x) for x in cur.fetchall()] if q.strip().lower().startswith('select') else None; c.commit(); c.close(); return r
def ok(n,c,x=''): print(('PASS ' if c else 'FAIL ')+n+('' if c else ' '+str(x)[:400]))

c,d=call('POST','/api/v1/auth/login',{'phone':'01711000001','password':'Password@123'}); tok=d['data']['token']
c,d=call('GET','/api/v1/addresses',tok=tok); a=d['data'] if isinstance(d['data'],list) else d['data'].get('data',[])
if not a:
    c,g=call('GET','/api/v1/geo/divisions'); div=g['data'][0]['id']
    c,g=call('GET','/api/v1/geo/districts?division_id=%s'%div); dis=g['data'][0]['id']
    call('POST','/api/v1/addresses',{'name':'T','phone':'01711000001','address_line':'x','division_id':div,'district_id':dis,'type':'home'},tok)
    c,d=call('GET','/api/v1/addresses',tok=tok); a=d['data'] if isinstance(d['data'],list) else d['data'].get('data',[])
aid=a[0]['id']
P={s:sql("select id,price from products where seller_id=? and is_active=1 and quantity>10 order by id limit 1",s)[0] for s in (1,2,3)}

# 1) mixed cart -> one order per seller, each with its own commission
c,d=call('POST','/api/v1/orders',{'address_id':aid,'items':[{'product_id':P[1]['id'],'quantity':1},{'product_id':P[2]['id'],'quantity':2},{'product_id':P[3]['id'],'quantity':1}],'payment_method':'cod'},tok)
ok('mixed cart accepted (201)',c==201,(c,d))
dd=d['data']; ords=dd['orders']
ok('split into 3 orders (one per seller)',len(ords)==3 and sorted(o['seller_id'] for o in ords)==[1,2,3],ords)
rows={r['seller_id']:r for r in sql("select * from orders where id in (%s)"%','.join(str(o['id']) for o in ords))}
rate={1:10,2:8,3:12}
good=True
for s,r in rows.items():
    sub=float(r['subtotal']); exp=round(sub*rate[s]/100,2)
    good &= abs(float(r['commission_amount'])-exp)<0.02 and abs(float(r['seller_payout'])-(sub-exp))<0.02
ok("each order uses ITS seller's commission rate (10/8/12 %)",good,[(s,r['subtotal'],r['commission_amount'],r['seller_payout']) for s,r in rows.items()])
ok('orders share one checkout_group',len({r['checkout_group'] for r in rows.values()})==1 and rows[1]['checkout_group'],rows[1]['checkout_group'])
ok('grand_total equals sum of orders',abs(dd['grand_total']-sum(float(r['total_amount']) for r in rows.values()))<0.01,dd['grand_total'])
ok('each order holds only its own seller items',all(sql("select count(*) n from order_items oi join products p on p.id=oi.product_id where oi.order_id=? and p.seller_id!=?",r['id'],s)[0]['n']==0 for s,r in rows.items()))

# 2) single-seller cart stays a single order
c,d=call('POST','/api/v1/orders',{'address_id':aid,'items':[{'product_id':P[1]['id'],'quantity':1}],'payment_method':'cod'},tok)
ok('single seller -> 1 order, no group',c==201 and len(d['data']['orders'])==1 and sql("select checkout_group g from orders where id=?",d['data']['id'])[0]['g'] is None,d)

# 3) wallet: one debit, ledger chain
uid=sql("select id from users where phone='01711000001'")[0]['id']
sql("update users set wallet_balance=100000 where id=?",uid)
c,d=call('POST','/api/v1/orders',{'address_id':aid,'items':[{'product_id':P[1]['id'],'quantity':1},{'product_id':P[2]['id'],'quantity':1}],'payment_method':'wallet'},tok)
ok('wallet mixed order 201',c==201,(c,d))
tot=d['data']['grand_total']; ids=[o['id'] for o in d['data']['orders']]
bal=float(sql("select wallet_balance b from users where id=?",uid)[0]['b'])
ok('wallet debited exactly the grand total once',abs(bal-(100000-tot))<0.01,(bal,tot))
led=sql("select balance_before,balance_after,amount from transactions where reference_type='order_payment' and reference_id in (%s) order by id"%','.join(map(str,ids)))
ok('ledger entries chain correctly',len(led)==2 and abs(led[0]['balance_after']-led[1]['balance_before'])<0.01 and abs(led[1]['balance_after']-bal)<0.01,led)
ok('wallet orders are paid',all(r['payment_status']=='paid' for r in sql("select payment_status from orders where id in (%s)"%','.join(map(str,ids)))))

# 4) suspended seller's products cannot be bought
sql("update sellers set status='suspended' where id=3")
c,d=call('POST','/api/v1/orders',{'address_id':aid,'items':[{'product_id':P[3]['id'],'quantity':1}],'payment_method':'cod'},tok)
ok('suspended seller product refused (422)',c==422,(c,d))
sql("update sellers set status='active' where id=3")

# 5) auto-confirm setting
sql("insert into settings(key,value,'group',type,created_at,updated_at) values('auto_confirm_order','1','general','text',datetime('now'),datetime('now')) on conflict(key) do update set value='1'")
call('GET','/api/v1/settings/public')
import subprocess; subprocess.run(['php','artisan','cache:clear','-q'])
c,d=call('POST','/api/v1/orders',{'address_id':aid,'items':[{'product_id':P[1]['id'],'quantity':1}],'payment_method':'cod'},tok)
ok('auto_confirm_order=on -> COD order starts confirmed',c==201 and sql("select status s from orders where id=?",d['data']['id'])[0]['s']=='confirmed',d)
sql("update settings set value='0' where key='auto_confirm_order'"); subprocess.run(['php','artisan','cache:clear','-q'])
c,d=call('POST','/api/v1/orders',{'address_id':aid,'items':[{'product_id':P[1]['id'],'quantity':1}],'payment_method':'cod'},tok)
ok('auto_confirm_order=off -> stays pending',sql("select status s from orders where id=?",d['data']['id'])[0]['s']=='pending',d)

# 6) reviews: verified purchase + enable/auto-approve switches
c,d=call('POST','/api/v1/reviews',{'product_id':P[3]['id'],'rating':5,'comment':'Great product!'},tok)
ok('review without delivered purchase refused (403)',c==403,(c,d))
