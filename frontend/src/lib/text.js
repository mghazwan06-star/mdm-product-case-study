// Wording helpers. Always real singular/plural, never "(s)".

/**
 * "1 laptop", "2 laptops".
 */
export function plural(count, singular, pluralForm = `${singular}s`) {
  return `${count} ${count === 1 ? singular : pluralForm}`
}

export function laptops(count) {
  return plural(count, 'laptop')
}

export function people(count) {
  return plural(count, 'person', 'people')
}

/**
 * Laptop count for the people list: never blank, never "0".
 */
export function laptopCount(count) {
  return count === 0 ? 'No laptops' : laptops(count)
}
