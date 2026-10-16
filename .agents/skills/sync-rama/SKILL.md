---
name: sync-rama
description: Comprueba si el clon actual está al día con origin en la rama en la que está. Usar solo cuando el usuario invoque /sync-rama.
disable-model-invocation: true
---

# Sync rama

Comprueba la rama de trabajo contra `origin`. No edites archivos, no hagas commit, no hagas push, no hagas `git pull`, no hagas `reset` y no ejecutes `php spark migrate`.

## Pasos

1. Desde la raíz del repositorio, ejecuta:

```bash
bash .agents/skills/sync-rama/scripts/check.sh
```

2. Muestra la salida del script tal cual.
3. Traduce solo la línea `RESULTADO` a una frase. Si dice que el clon va detrás, indica el comando `git pull --ff-only` que imprimió el script y espera a que la persona lo ejecute. No lo lances tú.
