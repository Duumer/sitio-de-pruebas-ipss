/* CTF IPSS - Login + theme toggle */
(function(){
  const root=document.documentElement;
  const saved=localStorage.getItem('ctf-theme');
  if(saved) root.setAttribute('data-theme',saved);
  function syncIcons(){
    const dark=root.getAttribute('data-theme')==='dark';
    document.querySelectorAll('.icon-sun').forEach(e=>e.style.display=dark?'none':'');
    document.querySelectorAll('.icon-moon').forEach(e=>e.style.display=dark?'':'none');
  }
  syncIcons();
  const t=document.getElementById('theme-toggle');
  if(t) t.addEventListener('click',()=>{
    const next=root.getAttribute('data-theme')==='dark'?'light':'dark';
    root.setAttribute('data-theme',next);localStorage.setItem('ctf-theme',next);syncIcons();
  });
  document.querySelectorAll('.toggle-password').forEach(btn=>{
    btn.addEventListener('click',()=>{
      const input=btn.parentElement.querySelector('input');
      const open=btn.querySelector('.eye-open'),closed=btn.querySelector('.eye-closed');
      if(input.type==='password'){input.type='text';open.style.display='none';closed.style.display='';}
      else{input.type='password';open.style.display='';closed.style.display='none';}
    });
  });
})();
