document.addEventListener("DOMContentLoaded",function(){
var botones=document.querySelectorAll(".boton-panel");
botones.forEach(function(b){
b.addEventListener("click",function(e){
var nombre=e.currentTarget.textContent.trim();
alert("Abrir: "+nombre);
console.log("Acción seleccionada:",nombre)
})
})
})
