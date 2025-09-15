<?php
namespace App\Config;

class Promise
{
    const PENDING = 0;
    const FULFILLED = 1;
    const REJECTED = 2;

    protected $state = self::PENDING;
    protected $value;
    protected $reason;
    protected $successCallbacks = [];
    protected $failureCallbacks = [];

	// Constructor: recibe un callback y maneja excepciones
    public function __construct(callable $callback)
    {
        try {
            $callback([$this, 'resolve'], [$this, 'reject']);
        } catch (\Exception $e) {
            $this->reject($e);
        }
    }

    // Método para agregar callbacks para el manejo de éxito y error
    public function then(callable $onFulfilled = null, callable $onRejected = null)
    {
        $promise = new self(function () {});

        // Agregar callbacks al array de successCallbacks
        // que se ejecutarán cuando la promesa se resuelva con éxito
        // Los callbacks pueden retornar promesas o valores
        // que serán resueltos por la nueva promesa $promise
        // Manejo de excepciones incluido
        if ($onFulfilled) {
            $this->successCallbacks[] = function ($value) use ($onFulfilled, $promise) {
                try {
                    $result = call_user_func($onFulfilled, $value);
                    if ($result instanceof self) {
                        $result->then([$promise, 'resolve'], [$promise, 'reject']);
                    } else {
                        $promise->resolve($result);
                    }
                } catch (\Exception $e) {
                    $promise->reject($e);
                }
            };
        }

        // Agregar callbacks al array de failureCallbacks
        // para manejar el rechazo de la promesa
        if ($onRejected) {
            $this->failureCallbacks[] = function ($reason) use ($onRejected, $promise) {
                try {
                    $result = call_user_func($onRejected, $reason);
                    if ($result instanceof self) {
                        $result->then([$promise, 'resolve'], [$promise, 'reject']);
                    } else {
                        $promise->resolve($result);
                    }
                } catch (\Exception $e) {
                    $promise->reject($e);
                }
            };
        }

		// Ejecutar los callbacks inmediatamente si la promesa ya se resolvió
        if ($this->state === self::FULFILLED) {
            call_user_func(end($this->successCallbacks), $this->value);
        }

        if ($this->state === self::REJECTED) {
            call_user_func(end($this->failureCallbacks), $this->reason);
        }

        return $promise;
    }

    // Método para agregar un callback específico para manejar rechazos
    public function catch(callable $onRejected)
    {
        return $this->then(null, $onRejected);
    }

    // Resolver la promesa con éxito
    public function resolve($value)
    {
        if ($this->state === self::PENDING) {
            if ($value === $this) {
                throw new \Exception('A promise cannot be resolved with itself.');
            }

            if ($value instanceof self) {
                return $value->then([$this, 'resolve'], [$this, 'reject']);
            }

            if (is_object($value) || is_callable($value)) {
                try {
                    $then = is_callable([$value, 'then']) ? [$value, 'then'] : null;
                } catch (\Exception $e) {
                    return $this->reject($e);
                }

                if (is_callable($then)) {
                    try {
                        call_user_func($then, [$this, 'resolve'], [$this, 'reject']);
                    } catch (\Exception$e) {
                        return$this->reject($e);
                    }
                    return;
                }
            }

            $this->state = self::FULFILLED;
            $this->value =$value;

            foreach ($this->successCallbacks as$callback) {
            	call_user_func($callback,$value);
            }
        }
    }

    // Rechazar la promesa
    public function reject($reason)
    {
    	if ($this->state ===self::PENDING) {
    		$this->state=self::REJECTED;
    		$this->reason=$reason;
    		foreach ($this->failureCallbacks as $callback) {
    			call_user_func($callback,$reason);
    		}
    	}
    }

    // Método estático para resolver una promesa con un valor
    public static function resolveWith($value) {
		return new self(function ($resolve) use ($value) {
			$resolve($value);
		});
	}

	// Método estático para rechazar una promesa con un motivo
    public static function rejectWith($reason) {
		return new self(function ($_, $reject) use ($reason) {
			$reject($reason);
		});
	}

	// Método estático para ejecutar un conjunto de promesas en paralelo
	public static function all(array$promises) {
		return new self(function ($resolve, $reject) use ($promises) {
			$results=[];
			$remaining=count($promises);
			foreach ($promises as $i => $promise) {
				if (!($promise instanceof self)) {
					$promise=self::resolveWith($promise);
				}
				$promise->then(function ($value) use (&$results, &$remaining, $i, $resolve) {
					$results[$i]=$value;
					if (--$remaining===0) {
						$resolve(array_values($results));
					}
				}, $reject);
			}
		});
	}

	// Método estático para ejecutar un conjunto de promesas en paralelo y resolver con la primera en completarse
	public static function race(array $promises) {
	    return new self(function ($resolve, $reject) use ($promises) {
	        foreach ($promises as $promise) {
	            if (!($promise instanceof self)) {
	                $promise = self::resolveWith($promise);
	            }
	            $promise->then($resolve, $reject);
	        }
	    });
	}

}