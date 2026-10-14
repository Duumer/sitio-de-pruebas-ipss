/* CTF IPSS - Dashboard estudiante: cards, modal, flags, hints, simuladores seguros */
(function(){
  const cfg=window.CTF_CONFIG||{apiBase:'/api'};
  const grid=document.getElementById('challenges-grid');
  const scoreBody=document.getElementById('scoreboard-body');
  const modal=document.getElementById('challenge-modal');
  const modalBody=document.getElementById('modal-body');
  const modalTitle=document.getElementById('modal-title');
  let challenges=[],statuses={},activeFilter='all',usedHintId=null;
  // Tema + menú
  const root=document.documentElement;
  if(localStorage.getItem('ctf-theme')) root.setAttribute('data-theme',localStorage.getItem('ctf-theme'));
  const syncIcons=()=>{const d=root.getAttribute('data-theme')==='dark';
    document.querySelectorAll('.icon-sun').forEach(e=>e.style.display=d?'none':'');
    document.querySelectorAll('.icon-moon').forEach(e=>e.style.display=d?'':'none');};
  syncIcons();
  document.getElementById('theme-toggle')?.addEventListener('click',()=>{
    const n=root.getAttribute('data-theme')==='dark'?'light':'dark';
    root.setAttribute('data-theme',n);localStorage.setItem('ctf-theme',n);syncIcons();});
  document.querySelectorAll('.user-menu-trigger').forEach(b=>b.addEventListener('click',e=>{e.stopPropagation();b.parentElement.classList.toggle('open');}));
  document.addEventListener('click',()=>document.querySelectorAll('.user-menu.open').forEach(m=>m.classList.remove('open')));
  // Tabs filtro
  document.querySelectorAll('.tab-btn').forEach(b=>b.addEventListener('click',()=>{
    document.querySelectorAll('.tab-btn').forEach(x=>{x.classList.remove('active');x.setAttribute('aria-selected','false');});
    b.classList.add('active');b.setAttribute('aria-selected','true');activeFilter=b.dataset.category;renderCards();}));
  // Modal
  const openModal=()=>{modal.hidden=false;};
  const closeModal=()=>{modal.hidden=true;modalBody.innerHTML='';usedHintId=null;};
  modal.querySelectorAll('.modal-close,.modal-overlay').forEach(el=>el.addEventListener('click',closeModal));
  document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal();});
  function toast(msg,type='info'){const c=document.getElementById('toast-container');const d=document.createElement('div');d.className='toast '+type;d.textContent=msg;c.appendChild(d);setTimeout(()=>d.remove(),4500);}
  function esc(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
  // Carga inicial
  async function load(){
    try{
      const r=await fetch(cfg.apiBase+'/dashboard_data.php');
      const d=await r.json();
      if(!d.success) throw 0;
      challenges=d.challenges||[];statuses=d.statuses||{};
      document.getElementById('total-score').textContent=Number(d.user.score).toLocaleString();
      document.getElementById('current-rank').textContent=d.user.rank;
      document.getElementById('completed-count').textContent=d.user.completed;
      document.getElementById('total-challenges').textContent=challenges.length;
      document.getElementById('progress-bar').style.width=d.user.progress+'%';
      renderCards();renderRanking(d.ranking||[]);
    }catch(e){grid.innerHTML='<p class="text-muted">Error al cargar desafíos.</p>';}
  }
  function statusOf(id){return statuses[id]||'no_iniciado';}
  function renderCards(){
    const list=challenges.filter(c=>activeFilter==='all'||c.categoria_slug===activeFilter);
    grid.innerHTML=list.map(c=>{
      const st=statusOf(c.id);
      const cls=st==='completado'?'solved':(st==='en_progreso'?'in-progress':'');
      const pill=st==='completado'?'Completado':(st==='en_progreso'?'En progreso':'No iniciado');
      return '<article class="challenge-card '+cls+'">'
      +'<div class="card-top"><span class="category-badge" style="background:'+esc(c.categoria_color)+'20;color:'+esc(c.categoria_color)+'">'+esc(c.categoria_nombre)+'</span>'
      +'<span class="difficulty '+esc(c.dificultad)+'">'+esc(c.dificultad)+'</span></div>'
      +'<h3>'+esc(c.titulo)+'</h3><p class="card-desc">'+esc(c.descripcion)+'</p>'
      +'<div class="card-meta"><span class="points">'+c.puntos+' pts</span><span class="status-pill '+st+'">'+pill+'</span></div>'
      +'<button class="btn btn-primary btn-sm open-challenge" data-id="'+c.id+'">'+(st==='completado'?'Revisar':'Acceder al reto')+'</button></article>';
    }).join('')||'<p class="text-muted">Sin desafíos en esta categoría.</p>';
    grid.querySelectorAll('.open-challenge').forEach(b=>b.addEventListener('click',()=>openChallenge(b.dataset.id)));
  }
  function renderRanking(rows){
    scoreBody.innerHTML=rows.map(r=>'<tr class="'+(String(r.usuario_id)===String(cfg.userId)?'me':'')+'"><td>#'+r.posicion+'</td><td>'+esc(r.nombre)+'</td><td>'+r.puntaje_total+'</td><td>'+r.desafios_completados+'</td><td>'+esc(r.ultima_resolucion||'—')+'</td></tr>').join('')||'<tr><td colspan="5">Sin datos</td></tr>';
  }
  async function openChallenge(id){
    modalBody.innerHTML='<p class="text-muted">Cargando…</p>';openModal();
    try{
      const r=await fetch(cfg.apiBase+'/challenge_detail.php?id='+encodeURIComponent(id));
      const d=await r.json();
      if(!d.success) throw 0;
      const c=d.challenge;
      modalTitle.textContent=c.titulo;
      const dl=c.archivo_adjunto?'<p><a class="btn btn-secondary btn-sm" href="'+cfg.apiBase+'/download_file.php?id='+c.id+'">Descargar evidencia: '+esc(c.archivo_nombre||c.archivo_adjunto)+'</a></p>':'';
      const hints=(d.hints||[]).map(h=>'<div><button class="btn btn-secondary btn-sm show-hint" data-hint="'+h.id+'" data-ch="'+c.id+'">Pista ('+h.penalizacion_puntos+' pts)</button> <span class="hint-out text-muted"></span></div>').join('');
      let sim='';
      if(c.slug==='idor-simulado') sim=simIdor();
      else if(c.slug==='sqli-simulada') sim=simSqli();
      else if(c.slug==='cripto-capas') sim=simCrypto();
      modalBody.innerHTML='<div class="challenge-enunciado">'+c.enunciado+'</div>'+dl
        +'<div class="sim-box"><h4>Simulador seguro (sin vulnerabilidades reales)</h4>'+sim+'</div>'
        +'<div><h4>Pistas</h4>'+(hints||'<p class="text-muted">Sin pistas.</p>')+'</div>'
        +(d.can_submit?'<hr><h4>Enviar flag + evidencia de mitigación</h4>'
          +'<div class="form-group"><label>Flag (formato IPSS{...})</label><input class="mono" id="flag-input" placeholder="IPSS{...}" autocomplete="off"></div>'
          +'<div class="form-group"><label>Análisis causa raíz + mitigación (obligatorio)</label><textarea id="evidence-input" placeholder="Describe el hallazgo y la contramedida técnica…"></textarea></div>'
          +'<button class="btn btn-primary" id="send-flag">Validar bandera</button>'
          :'<p class="badge badge-success">Completado — ya no puedes re-enviar</p>');
      bindSim(c.slug);
      modalBody.querySelectorAll('.show-hint').forEach(b=>b.addEventListener('click',async()=>{
        const out=b.parentElement.querySelector('.hint-out');out.textContent='Solicitando…';
        const rr=await fetch(cfg.apiBase+'/get_hint.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({challenge_id:+b.dataset.ch,hint_id:+b.dataset.hint})});
        const dd=await rr.json();
        if(dd.success){out.textContent=dd.hint+' (−'+dd.penalty+' pts)';usedHintId=+b.dataset.hint;}
        else out.textContent=dd.message;
      }));
      const send=modalBody.querySelector('#send-flag');
      if(send) send.addEventListener('click',async()=>{
        const flag=modalBody.querySelector('#flag-input').value.trim();
        const ev=modalBody.querySelector('#evidence-input').value.trim();
        if(!flag){toast('Ingresa la flag','warning');return;}
        if(ev.length<20){toast('La evidencia debe tener al menos 20 caracteres','warning');return;}
        send.disabled=true;
        const rr=await fetch(cfg.apiBase+'/submit_flag.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({challenge_id:+c.id,flag:flag,evidence:ev,hint_id:usedHintId})});
        const dd=await rr.json();
        if(dd.success){toast(dd.message,'success');closeModal();load();}
        else{toast(dd.message,'error');send.disabled=false;}
      });
    }catch(e){modalBody.innerHTML='<p class="text-muted">Error al cargar el desafío.</p>';}
  }
  // --- Simuladores ---
  const FAKE_USERS={1:{name:'admin_ipss',role:'administrador',email:'admin@ipss.cl',flag:'IPSS{idor_access_bypass_demo}'},2:{name:'estudiante_demo',role:'estudiante',email:'estudiante@ipss.cl'},3:{name:'docente_demo',role:'docente',email:'docente@ipss.cl'},4:{name:'invitado',role:'invitado',email:'-'}};
  function simIdor(){return '<p class="text-muted">Consulta de perfil mockeada. Tu sesión es <code>user_id=2</code>. Manipula el parámetro.</p><div class="form-group"><label>user_id</label><input id="sim-idor-id" value="2"></div><button class="btn btn-secondary btn-sm" id="sim-idor-go">Consultar perfil</button><div class="sim-result" id="sim-idor-out">—</div>';}
  function simSqli(){return '<p class="text-muted">Login falso aislado. Query: <code>SELECT * FROM u WHERE user=\'$in\' AND pass=\'$p\'</code> (dataset ficticio en memoria).</p><div class="form-group"><label>Usuario</label><input id="sim-sqli-u" placeholder="admin"></div><div class="form-group"><label>Contraseña</label><input id="sim-sqli-p" placeholder="***"></div><button class="btn btn-secondary btn-sm" id="sim-sqli-go">Intentar login</button><div class="sim-result" id="sim-sqli-out">—</div>';}
  function simCrypto(){return '<p class="text-muted">Cadena interceptada (3 capas: Base64 → Hex → César):</p><p><code>VDROd2MySkhPSE5KTW1SM1UxRlNXa1ZXTmxwMA==</code></p><button class="btn btn-secondary btn-sm" id="sim-crypto-go">Decodificar paso a paso</button><div class="sim-result" id="sim-crypto-out">—</div>';}
  function bindSim(slug){
    if(slug==='idor-simulado'){
      modalBody.querySelector('#sim-idor-go')?.addEventListener('click',()=>{
        const id=modalBody.querySelector('#sim-idor-id').value.trim();
        const u=FAKE_USERS[id];
        const out=modalBody.querySelector('#sim-idor-out');
        if(!u){out.textContent='[404] Perfil no encontrado para user_id='+id;return;}
        let t='[200] Perfil user_id='+id+'\nNombre: '+u.name+'\nRol: '+u.role+'\nEmail: '+u.email;
        if(String(id)==='1'){t+='\n\n*** FLAG: '+u.flag+' ***\n(No hubo control de acceso: IDOR simulado. Mitiga con RBAC/ABAC.)';}
        else if(String(id)!=='2'){t+='\n(Acceso a perfil ajeno sin verificación — vulnerabilidad simulada.)';}
        out.textContent=t;statuses[1]='en_progreso';
      });
    }
    if(slug==='sqli-simulada'){
      modalBody.querySelector('#sim-sqli-go')?.addEventListener('click',()=>{
        const u=modalBody.querySelector('#sim-sqli-u').value;
        const out=modalBody.querySelector('#sim-sqli-out');
        const payload=u.toUpperCase();
        if(payload.includes("' OR '1'='1")||payload.includes("' OR \"1\"=\"1")||payload.includes("'--")||payload.includes("' OR 1=1")){
          out.textContent="[200] Login bypass exitoso como 'admin'\nQuery evaluada: ... WHERE user='"+u+"' ... → TRUE\n\n*** FLAG: IPSS{sqli_simulated_bypass} ***\n(Mitiga con Prepared Statements.)";
        }else{out.textContent="[401] Credenciales inválidas para '"+u+"'. Prueba inyección clásica: ' OR '1'='1";}
      });
    }
    if(slug==='cripto-capas'){
      modalBody.querySelector('#sim-crypto-go')?.addEventListener('click',()=>{
        const out=modalBody.querySelector('#sim-crypto-out');
        try{
          const l1=atob('VDROd2MySkhPSE5KTW1SM1UxRlNXa1ZXTmxwMA==');
          let l2='';for(let i=0;i<l1.length;i+=2){l2+=String.fromCharCode(parseInt(l1.substr(i,2),16));}
          let l3='';for(const ch of l2){if(/[A-Za-z]/.test(ch)){const b=ch<='Z'?65:97;l3+=String.fromCharCode((ch.charCodeAt(0)-b-3+26)%26+b);}else l3+=ch;}
          out.textContent='Capa 1 (Base64): '+l1+'\nCapa 2 (Hex): '+l2+'\nCapa 3 (César-3): '+l3+'\n\nPista: la flag real está en el enunciado/DB; esto demuestra la técnica.';
        }catch(e){out.textContent='Error al decodificar.';}
      });
    }
  }
  load();
  setInterval(load,60000);
})();
