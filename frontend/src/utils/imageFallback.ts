export const setImageFallback = (event: Event, fallbackSrc: string) => {
  const target = event.target as HTMLImageElement | null

  if (!target || target.dataset.fallbackApplied === 'true') {
    return
  }

  target.dataset.fallbackApplied = 'true'
  target.src = fallbackSrc
}
