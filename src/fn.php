<?php

namespace Krak\Fn;

// ACCESS

function method($name, /* object */ $data, ...$optionalArgs) {
    return $data->{$name}(...$optionalArgs);
}
function prop($key, /* object */ $data, $else = null) {
    return \property_exists($data, $key) ? $data->{$key} : $else;
}
function index(/* string|*/ $key, $data, $else = null) {
    return \array_key_exists($key, $data) ? $data[$key] : $else;
}

function setProp($key, $value, /* object */ $data) {
    $data->{$key} = $value;
    return $data;
}

function setIndex(/* string|*/ $key, $value, $data) {
    $data[$key] = $value;
    return $data;
}

function setIndexIn($keys, $value, $data) {
    return \Krak\Fn\updateIndexIn($keys, function() use ($value) {
        return $value;
    }, $data);
}

function propIn($props, /* object */ $obj, $else = null) {
    foreach ($props as $prop) {
        if (!\is_object($obj) || !\property_exists($obj, $prop)) {
            return $else;
        }

        $obj = $obj->{$prop};
    }

    return $obj;
}

function indexIn($keys, $data, $else = null) {
    foreach ($keys as $part) {
        if (!\is_array($data) || !\array_key_exists($part, $data)) {
            return $else;
        }

        $data = $data[$part];
    }

    return $data;
}

function hasIndexIn($keys, $data) {
    foreach ($keys as $key) {
        if (!\is_array($data) || !\array_key_exists($key, $data)) {
            return false;
        }
        $data = $data[$key];
    }

    return true;
}

function updateIndexIn($keys, $update, $data) {
    $curData = &$data;
    foreach (\array_slice($keys, 0, -1) as $key) {
        if (!\array_key_exists($key, $curData)) {
            throw new \RuntimeException('Could not updateIn because the keys ' . \implode(' -> ', $keys) . ' could not be found.');
        }
        $curData = &$curData[$key];
    }

    $lastKey = $keys[count($keys) - 1];
    $curData[$lastKey] = $update($curData[$lastKey] ?? null);

    return $data;
}

// UTILITY

function assign($obj, $iter) {
    foreach ($iter as $key => $value) {
        $obj->{$key} = $value;
    }
    return $obj;
}

function join($sep, $iter) {
    return \Krak\Fn\reduce(function($acc, $v) use ($sep) {
        return $acc ? $acc . $sep . $v : $v;
    }, $iter, "");
}

function construct($className, ...$args) {
    return new $className(...$args);
}

function spread($fn, $data) {
    return $fn(...$data);
}

function dd($value, $dump = null, $die = null) {
    $dump = $dump ?: (function_exists('dump') ? 'dump' : 'var_dump');
    $dump($value);
    ($die ?? function() { die; })();
}

// SLICING

function takeWhile($predicate, $iter) {
    foreach ($iter as $k => $v) {
        if ($predicate($v)) {
            yield $k => $v;
        } else {
            return;
        }
    }
}

function dropWhile($predicate, $iter) {
    $stillDropping = true;
    foreach ($iter as $k => $v) {
        if ($stillDropping && $predicate($v)) {
            continue;
        } else if ($stillDropping) {
            $stillDropping = false;
        }

        yield $k => $v;
    }
}

function take($num, $iter) {
    return \Krak\Fn\slice(0, $iter, $num);
}

function drop($num, $iter) {
    return \Krak\Fn\slice($num, $iter);
}

function slice($start, $iter, $length = INF) {
    assert($start >= 0);

    $i = 0;
    $end = $start + $length - 1;
    foreach ($iter as $k => $v) {
        if ($start <= $i && $i <= $end) {
            yield $k => $v;
        }

        $i += 1;
        if ($i > $end) {
            return;
        }
    }
}

function head($iter) {
    foreach ($iter as $v) {
        return $v;
    }
}

function chunk($size, $iter) {
    assert($size > 0);

    $chunk = [];
    foreach ($iter as $v) {
        $chunk[] = $v;
        if (\count($chunk) == $size) {
            yield $chunk;
            $chunk = [];
        }
    }

    if ($chunk) {
        yield $chunk;
    }
}

function chunkBy($fn, $iter, $maxSize = null) {
    assert($maxSize === null || $maxSize > 0);
    $group = [];
    $groupKey = null;
    foreach ($iter as $v) {
        $curGroupKey = $fn($v);
        $shouldYieldGroup = ($groupKey !== null && $groupKey !== $curGroupKey)
            || ($maxSize !== null && \count($group) >= $maxSize);
        if ($shouldYieldGroup) {
            yield $group;
            $group = [];
        }

        $group[] = $v;
        $groupKey = $curGroupKey;
    }

    if (\count($group)) {
        yield $group;
    }
}

function groupBy($fn, $iter, $maxSize = null) {
    return \Krak\Fn\chunkBy($fn, $iter, $maxSize);
}


// GENERATORS

function range($start, $end, $step = null) {
    if ($start == $end) {
        yield $start;
    } else if ($start < $end) {
        $step = $step ?: 1;
        if ($step <= 0) {
            throw new \InvalidArgumentException('Step must be greater than 0.');
        }
        for ($i = $start; $i <= $end; $i += $step) {
            yield $i;
        }
    } else {
        $step = $step ?: -1;
        if ($step >= 0) {
            throw new \InvalidArgumentException('Step must be less than 0.');
        }
        for ($i = $start; $i >= $end; $i += $step) {
            yield $i;
        }
    }
}

// OPERATORS

function op($op, $b, $a) {
    switch ($op) {
    case '==':
    case 'eq':
        return $a == $b;
    case '!=':
    case 'neq':
        return $a != $b;
    case '===':
        return $a === $b;
    case '!==':
        return $a !== $b;
    case '>':
    case 'gt':
        return $a > $b;
    case '>=':
    case 'gte':
        return $a >= $b;
    case '<':
    case 'lt':
        return $a < $b;
    case '<=':
    case 'lte':
        return $a <= $b;
    case '+':
        return $a + $b;
    case '-':
        return $a - $b;
    case '*':
        return $a * $b;
    case '**':
        return $a ** $b;
    case '/':
        return $a / $b;
    case '%':
        return $a % $b;
    case '.':
        return $a . $b;
    default:
        throw new \LogicException('Invalid operator '.$op);
    }
}

function andf(...$fns) {
    return function($el) use ($fns) {
        foreach ($fns as $fn) {
            if (!$fn($el)) {
                return false;
            }
        }
        return true;
    };
}
function orf(...$fns) {
    return function($el) use ($fns) {
        foreach ($fns as $fn) {
            if ($fn($el)) {
                return true;
            }
        }
        return false;
    };
}

function chain(...$iters) {
    foreach ($iters as $iter) {
        foreach ($iter as $k => $v) {
            yield $k => $v;
        }
    }
}

function zip(...$iters) {
    if (count($iters) == 0) {
        return;
    }

    $iters = \array_map(iter::class, $iters);

    while (true) {
        $tup = [];
        foreach ($iters as $iter) {
            if (!$iter->valid()) {
                return;
            }
            $tup[] = $iter->current();
            $iter->next();
        }
        yield $tup;
    }
}


function flatMap($map, $iter) {
    foreach ($iter as $k => $v) {
        foreach ($map($v) as $k => $v) {
            yield $k => $v;
        }
    }
}

function flatten($iter, $levels = INF) {
    if ($levels == 0) {
        return $iter;
    } else if ($levels == 1) {
        foreach ($iter as $k => $v) {
            if (\is_iterable($v)) {
                foreach ($v as $k1 => $v1) {
                    yield $k1 => $v1;
                }
            } else {
                yield $k => $v;
            }
        }
    } else {
        foreach ($iter as $k => $v) {
            if (\is_iterable($v)) {
                foreach (flatten($v, $levels - 1) as $k1 => $v1) {
                    yield $k1 => $v1;
                }
            } else {
                yield $k => $v;
            }
        }
    }
}


function when($if, $then, $value) {
    return $if($value) ? $then($value) : $value;
}

function toPairs($iter) {
    foreach ($iter as $key => $val) {
        yield [$key, $val];
    }
}
function fromPairs($iter) {
    foreach ($iter as list($key, $val)) {
        yield $key => $val;
    }
}

function within($fields, $iter) {
    return \Krak\Fn\filterKeys(\Krak\Fn\Curried\inArray($fields), $iter);
}
function without($fields, $iter) {
    return \Krak\Fn\filterKeys(\Krak\Fn\Curried\not(\Krak\Fn\Curried\inArray($fields)), $iter);
}

function compact($iter) {
    foreach ($iter as $key => $val) {
        if ($val !== null) {
            yield $key => $val;
        }
    }
}

function arrayCompact($iter) {
    $vals = [];
    foreach ($iter as $key => $val) {
        if ($val !== null) {
            $vals[$key] = $val;
        }
    }
    return $vals;
}

function pad($size, $iter, $padValue = null) {
    $i = 0;
    foreach ($iter as $key => $value) {
        yield $value;
        $i += 1;
    }

    if ($i >= $size) {
        return;
    }

    foreach (\Krak\Fn\range($i, $size - 1) as $index) {
        yield $padValue;
    }
}


// ALIASES

function inArray($set, $item) {
    return \in_array($item, $set);
}

function arrayMap($fn, $data) {
    return \array_map($fn, \is_array($data) ? $data : \Krak\Fn\toArray($data));
}

function arrayFilter($fn, $data) {
    return \array_filter(\is_array($data) ? $data : \Krak\Fn\toArray($data), $fn);
}

function all($predicate, $iter) {
    foreach ($iter as $key => $value) {
        if (!$predicate($value)) {
            return false;
        }
    }

    return true;
}
function any($predicate, $iter) {
    foreach ($iter as $key => $value) {
        if ($predicate($value)) {
            return true;
        }
    }

    return false;
}
function search($predicate, $iter) {
    foreach ($iter as $value) {
        if ($predicate($value)) {
            return $value;
        }
    }
}
function indexOf($predicate, $iter) {
    foreach ($iter as $key => $value) {
        if ($predicate($value)) {
            return $key;
        }
    }
}

function trans($trans, $fn, $data) {
    return $fn($trans($data));
}
function not($fn, ...$args) {
    return !$fn(...$args);
}
function isInstance($class, $item) {
    return $item instanceof $class;
}

function isNull($val) {
    return \is_null($val);
}
function nullable($fn, $value) {
    return $value === null ? $value : $fn($value);
}

function partition($partition, $iter, $numParts = 2) {
    $parts = \array_fill(0, $numParts, []);
    foreach ($iter as $val) {
        $index = (int) $partition($val);
        $parts[$index][] = $val;
    }

    return $parts;
}

function map($predicate, $iter) {
    foreach ($iter as $key => $value) {
        yield $key => $predicate($value);
    }
}

function mapKeys($predicate, $iter) {
    foreach ($iter as $key => $value) {
        yield $predicate($key) => $value;
    }
}

function mapKeyValue($fn , $iter) {
    foreach ($iter as $key => $value) {
        $res = $fn([$key, $value]);
        yield $res[0] => $res[1];
    }
}

function mapOn($maps, $iter) {
    foreach ($iter as $key => $value) {
        if (isset($maps[$key])) {
            yield $key => $maps[$key]($value);
        } else {
            yield $key => $value;
        }
    }
}

function mapAccum($fn, $iter, $acc = null) {
    $data = [];
    foreach ($iter as $key => $value) {
        $res = $fn($acc, $value);
        $data[] = $res[1];
    }

    return [$res[0], $data];
}

function withState($fn, $initialState = null) {
    $state = $initialState;
    return function(...$args) use ($fn, &$state) {
        $res = $fn($state, ...$args);
        $state = $res[0];
        return $res[1];
    };
}

function arrayReindex($fn, $iter) {
    $res = [];
    foreach ($iter as $key => $value) {
        $res[$fn($value)] = $value;
    }
    return $res;
}

function reindex($fn, $iter) {
    foreach ($iter as $key => $value) {
        yield $fn($value) => $value;
    }
}

function reduce($reduce, $iter, $acc = null) {
    foreach ($iter as $key => $value) {
        $acc = $reduce($acc, $value);
    }
    return $acc;
}

function reduceKeyValue($reduce, $iter, $acc = null) {
    foreach ($iter as $key => $value) {
        $acc = $reduce($acc, [$key, $value]);
    }
    return $acc;
}

function filter($predicate, $iter) {
    foreach ($iter as $key => $value) {
        if ($predicate($value)) {
            yield $key => $value;
        }
    }
}
function filterKeys($predicate, $iter) {
    foreach ($iter as $key => $value) {
        if ($predicate($key)) {
            yield $key => $value;
        }
    }
}

function values($iter) {
    foreach ($iter as $v) {
        yield $v;
    }
}

function keys($iter) {
    foreach ($iter as $k => $v) {
        yield $k;
    }
}

function flip($iter) {
    foreach ($iter as $k => $v) {
        yield $v => $k;
    }
}

function curry($fn, $num = 1) {
    if ($num == 0) {
        return $fn;
    }

    return function($arg1) use ($fn, $num) {
        return curry(function(...$args) use ($fn, $arg1) {
            return $fn($arg1, ...$args);
        }, $num - 1);
    };
}

function placeholder() {
    static $v;

    $v = $v ?: new class {};
    return $v;
}
function _() {
    return placeholder();
}

function partial($fn, ...$appliedArgs) {
    return function(...$args) use ($fn, $appliedArgs) {
        list($appliedArgs, $args) = \array_reduce($appliedArgs, function($acc, $arg) {
            list($appliedArgs, $args) = $acc;
            if ($arg === \Krak\Fn\placeholder()) {
                $arg = array_shift($args);
            }

            $appliedArgs[] = $arg;
            return [$appliedArgs, $args];
        }, [[], $args]);

        return $fn(...$appliedArgs, ...$args);
    };
}

function autoCurry($args, $numArgs, $fn) {
    if (\count($args) >= $numArgs) {
        return $fn(...$args);
    }
    if (\count($args) == $numArgs - 1) {
        return \Krak\Fn\partial($fn, ...$args);
    }
    if (\count($args) == 0) {
        return \Krak\Fn\curry($fn, $numArgs - 1);
    }

    return \Krak\Fn\curry(
        \Krak\Fn\partial($fn, ...$args),
        ($numArgs - 1 - \count($args))
    );
}

function toArray($iter) {
    $data = [];
    foreach ($iter as $key => $val) {
        $data[] = $val;
    }
    return $data;
}

function toArrayWithKeys($iter) {
    $data = [];
    foreach ($iter as $key => $val) {
        $data[$key] = $val;
    }
    return $data;
}

function id($v) {
    return $v;
}


// UTILITY

function differenceWith($cmp, $a, $b) {
    return \Krak\Fn\filter(function($aItem) use ($cmp, $b) {
        return \Krak\Fn\indexOf(\Krak\Fn\partial($cmp, $aItem), $b) === null;
    }, $a);
}

function sortFromArray($fn, $orderedElements, $iter) {
    $data = [];
    $flippedElements = \array_flip($orderedElements);

    foreach ($iter as $value) {
        $key = $fn($value);
        if (!\array_key_exists($key, $flippedElements)) {
            throw new \LogicException('Cannot sort element key '  . $key . ' because it does not exist in the ordered elements.');
        }

        $data[$flippedElements[$key]] = $value;
    }

    ksort($data);
    return $data;
}

function retry($fn, $shouldRetry = null) {
    if (\is_null($shouldRetry)) {
        $shouldRetry = function($numRetries, \Throwable $t = null) { return true; };
    }
    if (\is_int($shouldRetry)) {
        $maxTries = $shouldRetry;
        if ($maxTries < 0) {
            throw new \LogicException("maxTries must be greater than or equal to 0");
        }
        $shouldRetry = function($numRetries, \Throwable $t = null) use ($maxTries) { return $numRetries <= $maxTries; };
    }
    if (!\is_callable($shouldRetry)) {
        throw new \InvalidArgumentException('shouldRetry must be an or callable');
    }

    $numRetries = 0;
    do {
        try {
           return $fn($numRetries);
        } catch (\Throwable $t) {}
        $numRetries += 1;
    } while ($shouldRetry($numRetries, $t));

    throw $t;
}

function pipe(...$fns) {
    return function(...$args) use ($fns) {
        $isFirstPass = true;
        foreach ($fns as $fn) {
            if ($isFirstPass) {
                $arg = $fn(...$args);
                $isFirstPass = false;
            } else {
                $arg = $fn($arg);
            }

        }
        return $arg;
    };
}

function compose(...$fns) {
    return \Krak\Fn\pipe(...\array_reverse($fns));
}

function stack($funcs, $last = null, $resolve = null) {
    return function(...$args) use ($funcs, $resolve, $last) {
        return \Krak\Fn\reduce(function($acc, $func) use ($resolve) {
            return function(...$args) use ($acc, $func, $resolve) {
                $args[] = $acc;
                $func = $resolve ? $resolve($func) : $func;
                return $func(...$args);
            };
        }, $funcs, $last ?: function() { throw new \LogicException('No stack handler was able to capture this request'); });
    };
}

function each($handle, $iter) {
    foreach ($iter as $v) {
        $handle($v);
    }
}
/** @deprecated */
function onEach($handle, $iter) {
    foreach ($iter as $v) {
        $handle($v);
    }
}

function iter($iter) {
    if (\is_array($iter)) {
        return new \ArrayIterator($iter);
    } else if ($iter instanceof \Iterator) {
        return $iter;
    } else if (\is_object($iter) || \is_iterable($iter)) {
        return (function($iter) {
            foreach ($iter as $key => $value) {
                yield $key => $value;
            }
        })($iter);
    } else if (\is_string($iter)) {
        return (function($s) {
            for ($i = 0; $i < \strlen($s); $i++) {
                yield $i => $s[$i];
            }
        })($iter);
    }

    throw new \LogicException('Iter could not be converted into an iterable.');
}
