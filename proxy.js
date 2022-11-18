const log = text => `Log: ${text}`

const fp = new Proxy(log, {
    apply(target, thisArg, argArray) {
        console.log('Calling fn...')
        return target.apply(thisArg, argArray).toUpperCase()
    }
})

class Person{
    constructor(name, age) {
        this.name = name;
        this.age = age;
    }
}

const PersonProxy = new Proxy(Person, {
    construct(target, argArray) {
        console.log('Construct...')

        return new target(...argArray)
    }
})

const p = new PersonProxy('Andrei', 34)
