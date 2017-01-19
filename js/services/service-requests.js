/**
 * Created by Kristopher on 11/16/2016.
 */
var requests = angular
    .module('sgdp.service-requests', [])
    .factory('Requests', reqService);

reqService.$inject = ['$q', '$http', 'Constants'];

function reqService($q, $http, Constants) {
    'use strict';

    var self = this;

    var loanTitles = {
        pp: "pr\u00E9stamos personales",
        vc: 'vales de caja'
    };

    var maxAmount = 0;
    var minAmount = 0;

    /**
     * Fetches the specified user's requests.
     *
     * @param fetchId - User's ID.
     * @returns {*}
     */
    self.getUserRequests = function (fetchId) {
        var qReq = $q.defer();
        $http.get('index.php/AgentHomeController/getUserRequests',
            {params: {fetchId: fetchId}})
            .then(
            function (response) {
                minAmount = parseInt(response.data.minReqAmount, 10);
                maxAmount = parseInt(response.data.maxReqAmount, 10);
                if (response.data.message === "success") {
                    if (typeof response.data.requests !== "undefined") {
                        qReq.resolve(self.filterRequests(response.data.requests));
                    } else {
                        qReq.resolve({});
                    }
                } else {
                    qReq.reject(response.data.error);
                }
            });
        return qReq.promise;
    };

    /**
     * Updates the given request.
     *
     * @param postData - data to be sent to the server for updating the request.
     * @returns {*} - promise containing the operation's result.
     */
    self.updateRequest = function (postData) {
        var qUpdate = $q.defer();
        $http.post('index.php/EditRequestController/updateRequest',
                   JSON.stringify(postData))
            .then(function (response) {
                      if (response.status == 200) {
                          qUpdate.resolve();
                      } else {
                          qUpdate.reject('Ha ocurrido un problema al intentar actualizar la solicitud. ' +
                                         'Por favor intente más tarde.');
                      }
                  });
        return qUpdate.promise;
    };

    /**
     * Edits the given request.
     *
     * @param postData - data to be sent to the server for editing the request.
     * @returns {*} - promise containing the operation's result.
     */
    self.editRequest = function (postData) {
        var qEdit = $q.defer();
        $http.post('index.php/EditRequestController/editRequest',
                   JSON.stringify(postData))
            .then(function (response) {
                      if (response.status == 200) {
                          qEdit.resolve();
                      } else {
                          qEdit.reject('Ha ocurrido un problema al intentar editar la solicitud. ' +
                                         'Por favor intente más tarde.');
                      }
                  });
        return qEdit.promise;
    };

    /**
     * Deletes the specified document from database.
     *
     * @param doc - doc obj to erase from database.
     * @returns {*} - promise with the operation's result.
     */
    self.deleteDocument = function (doc) {
        var qDelete = $q.defer();
        $http.post('index.php/AgentHomeController/deleteDocument',
                   JSON.stringify(doc)).then(
            function (response) {
                if (response.data.message == "success") {
                    // Update interface
                    qDelete.resolve();
                } else {
                    qDelete.reject('Ha ocurrido un error en el sistema. ' +
                                   'Por favor intente más tarde');
                }
            }
        );
        return qDelete.promise;
    };

    /**
     * Deletes the specified request (and it's documents) from database.
     *
     * @param request - the request obj to erase from database.
     * @returns {*} - promise with the operation's result.
     */
    self.deleteRequestUI = function (request) {
        var qDelReq = $q.defer();
        $http.post('index.php/DeleteController/deleteRequestUI',
                   JSON.stringify(request))
            .then(function (response) {
                      if (response.data.message == "success") {
                          qDelReq.resolve();
                      } else {
                          qDelReq.reject(response.data.message ? response.data.message :
                                         'Ha ocurrido un error en el sistema. Por favor intente más tarde');
                      }
                  });
        return qDelReq.promise;
    };

    /**
     * Eliminates the specified request from the system.
     *
     * @param rid - request id as an encoded token.
     * @returns {*} promise containing the operation's result.
     */
    self.deleteRequestJWT = function (rid) {
        var qEliminate = $q.defer();
        $http.post('index.php/DeleteController/deleteRequestJWT', {rid: rid})
            .then(
            function (response) {
                console.log(response);
                if (response.data.message == 'success') {
                    qEliminate.resolve();
                } else {
                    qEliminate.reject(response.data.message);
                }
            }

        );

        return qEliminate.promise;
    };

    /**
     * Validates a request through the specified token.
     *
     * @param token - JWT
     * @returns {*} - promise with the operation's result.
     */
    self.validate = function(token) {
        var qVal = $q.defer();

        $http.get('index.php/ValidationController/validate', {params: {token: token}})
            .then(
            function (response) {
                console.log(response);
                if (response.data.message === "success") {
                    qVal.resolve();
                } else {
                    qVal.reject(response.data.message);
                }
            }
        );
        return qVal.promise;
    };

    self.updateDocDescription = function (doc) {
        var updateDoc = $q.defer();
        $http.post('index.php/EditRequestController/' +
                   'updateDocDescription', JSON.stringify(doc)).then(
            function (response) {
                if (response.status == 200) {
                    updateDoc.resolve();
                } else {
                    updateDoc.reject('Ha ocurrido un error al intentar actualizar la descripción del documento.' +
                                     'Por favor intente más tarde.');
                }
            }
        );
        return updateDoc.promise;
    };

    /**
     * Returns the max amount of money the user can ask for in a request.
     *
     * @returns {number} - containing the max. amount the applicant can request.
     */
    self.getMaxAmount = function () {
        return maxAmount;
    };

    /**
     * Returns the min amount of money the user can ask for in a request.
     *
     * @returns {number} - containing the min. amount the applicant can request.
     */
    self.getMinAmount = function () {
        return minAmount;
    };

    /**
     * * Filters all requests by type.
     *
     * @param requests - Requests array returned by the server.
     * @returns {{}} - Obj containing arrays of different types of loans.
     */
    self.filterRequests = function (requests) {
        var req = {};
        var codes = Constants.LoanTypes;
        angular.forEach(codes, function (code) {
            req[self.mapLoanTypeAsCode(code)] = requests.filter(function (loan) {
                return loan.type == code;
            });
        });
        return req;
    };

    /**
     * Gets the different kind of requests' title.
     *
     * @returns {*} - a string corresponding to the loan type's title.
     */
    self.getRequestsListTitle = function () {
        return loanTitles;
    };

    /**
     * Gets the different request status types as strings.
     *
     * @returns {Array} containing all the statuses mapped as strings.
     */
    self.getStatusesTitles = function () {
        var codes = Constants.Statuses;
        var titles = [];
        angular.forEach(codes, function (code) {
            titles.push(self.mapStatus(code));
        });

        return titles;
    };

    /**
     * Gets all the existing statuses codes.
     *
     * @returns {Array} containing all the request statuses.
     */
    self.getAllStatuses = function () {
        var statuses = [];
        angular.forEach(Constants.Statuses, function (status) {
            statuses.push(status);
        });
        return statuses;
    };

    /**
     * Gets the different request types as strings.
     *
     * @returns {Array} containing all the loan types mapped as strings.
     */
    self.getLoanTypesTitles = function () {
        var codes = Constants.LoanTypes;
        var titles = [];
        angular.forEach(codes, function (code) {
            titles.push(self.mapLoanType(code));
        });

        return titles;
    };

    /**
     * Gets all the existing loan types.
     *
     * @returns {Array} containing all the requests loan types.
     */
    self.getAllLoanTypes = function () {
        var loanTypes = [];
        angular.forEach(Constants.LoanTypes, function (type) {
            loanTypes.push(type);
        });
        return loanTypes;
    };

    /**
     * Maps the specified (int) type to it's corresponding string code type.
     *
     * @param type - loan type's code.
     * @returns {*} - string containing the corresponding mapped string code type.
     */
    self.mapLoanTypeAsCode = function (type) {
        switch (type) {
            case Constants.LoanTypes.PERSONAL:
                return 'pp';
                break;
            case Constants.LoanTypes.CASH_VOUCHER:
                return 'vc';
                break;
            default:
                return type;
        }
    };

    /**
     * Maps the specified (int) type to it's corresponding string type.
     *
     * @param type - loan type's code.
     * @returns {*} - string containing the corresponding mapped string type.
     */
    self.mapLoanType = function (type) {
        switch (type) {
            case Constants.LoanTypes.PERSONAL:
                return 'Préstamo Personal';
                break;
            case Constants.LoanTypes.CASH_VOUCHER:
                return 'Vale de Caja';
                break;
            default:
                return type;
        }
    };

    /**
     * Maps the specified (int) statusCode to it's corresponding string type.
     *
     * @param statusCode - request's status code.
     * @returns {*} - string contaning the corresponding mapped type.
     */
    self.mapStatus = function (statusCode) {
        switch (statusCode) {
            case Constants.Statuses.RECEIVED:
                return 'Recibida';
                break;
            case Constants.Statuses.APPROVED:
                return 'Aprobada';
                break;
            case Constants.Statuses.REJECTED:
                return 'Rechazada';
                break;
            default:
                return statusCode;
        }
    };

    /**
     * Initializes a list type as false.
     */
    self.initializeListType = function () {
        var list = {};
        var codes = Constants.LoanTypes;
        angular.forEach(codes, function (code) {
            list[self.mapLoanTypeAsCode(code)] = false;
        });
        return list;
    };

    /**
     * Calculates the total loans contained in the specified array obj.
     *
     * @param filteredRequests - Filtered requests container, containing
     * the different loans.
     * @returns {number} - containing the total amount of loans in the array obj.
     */
    self.getTotalLoans = function (filteredRequests) {
        var total = 0;
        angular.forEach(filteredRequests, function (loan) {
            total += loan.length;
        });
        return total;
    };

    /**
     * Finds the specified loan within the requests obj.
     * @param requests - object containing all the requests.
     * @param id - corresponding loan's id.
     */
    self.findRequest = function (requests, id) {
        var index = {};
        var found = false;

        angular.forEach(requests, function (request, rKey) {
            var i = 0;
            while (i < request.length && !found) {
                if (request[i].id === id) {
                    index.request = rKey;
                    index.loan = i;
                    found = true;
                }
                i++;
            }
        });
        return index;
    };

    /**
     * Creates the new request.
     *
     * @param postData - Data to be sent to the server for the request creation.
     * @returns {*} - promise containing the operation's result.
     */
    self.createRequest = function (postData) {
        var qReqCreation = $q.defer();
        $http.post('index.php/NewRequestController/createRequest',
                   JSON.stringify(postData))
            .then(function (response) {
                      console.log(response);
                      if (response.data.message == "success") {
                          qReqCreation.resolve();
                      } else {
                          qReqCreation.reject('Ha ocurrido un error al crear su solicitud. ' +
                                              'Por favor intente más tarde');
                      }
                  });
        return qReqCreation.promise;
    };

    /**
     * Creates POST data for the request doc.
     *
     * @param userId - user applicant's id.
     * @param requestNumb - number of the request (i.e, [type].[number]).
     * @returns {{lpath: string, description: string, docName: string}}
     */
    self.createRequestDocData = function (userId, requestNumb) {
        var docName = 'Constancia';
        return {
            lpath: userId + '.' + requestNumb + '.' + docName + '.pdf',
            description: 'Documento declarativo referente a la solicitud',
            docName: docName
        }
    };

    /**
     * Edits the request's email address.
     *
     * @param reqId - selected request's id.
     * @param newAddress - new email address.
     */
    self.editEmail = function (reqId, newAddress) {
        var qEmail = $q.defer();

        var postData = {reqId: reqId, newAddress: newAddress};
        $http.post('index.php/EditRequestController/updateEmail', postData)
            .then(
            function (response) {
                console.log(response);
                if (response.data.message == "success") {
                    qEmail.resolve();
                } else {
                    qEmail.reject('Ha ocurrido un error al actualizar la dirección. ' +
                                  'de correo. Por favor intente más tarde.');
                }
            });
        return qEmail.promise;
    };

    /**
     * Sends a validation email for the specified request.
     *
     * @param reqId - request id.
     */
    self.sendValidation = function(reqId) {
        var qValidation = $q.defer();

        $http.post('index.php/ApplicantHomeController/sendValidation', reqId)
            .then(
            function (response) {
                console.log(response);
                if (response.data.message == "success") {
                    qValidation.resolve();
                } else {
                    qValidation.reject('Ha ocurrido un error al actualizar la dirección. ' +
                                  'de correo. Por favor intente más tarde.');
                }
            });
        return qValidation.promise;
    };

    /**
     * Returns a specific doc's download link.
     *
     * @param docPath - Doc's name on disk.
     * @returns {string} - Formed URL containing link to download doc.
     */
    self.getDocDownloadUrl = function (docPath) {
        return 'index.php/ApplicantHomeController/download?lpath=' + docPath;
    };

    /**
     * Returns all docs' download link.
     *
     * @param docs - Array containing all docs.
     * @returns {string} - Formed URL containing link to download doc.
     */
    self.getAllDocsDownloadUrl = function (docs) {
        // Bits of pre-processing before passing objects to URL
        var paths = [];
        angular.forEach(docs, function (doc) {
            paths.push(doc.lpath);
        });
        return 'index.php/ApplicantHomeController/downloadAll?docs=' + JSON.stringify(paths);
    };

    return self;
}
