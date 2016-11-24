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
                maxAmount = response.data.maxReqAmount;
                if (response.data.message === "success") {
                    if (typeof response.data.requests !== "undefined") {
                        qReq.resolve(filterRequests(response.data.requests));
                    } else {
                        qReq.resolve(filterRequests([]));
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
     */
    self.updateRequest = function (postData) {
        var qUpdate = $q.defer();
        $http.post('index.php/EditRequestController/updateRequest',
                   JSON.stringify(postData))
            .then(function (response) {
                      if (response.status == 200) {
                          qUpdate.resolve();
                      } else {
                          qUpdate.reject('Ha ocurrido un problema al actualizar la solicitud. ' +
                                         'Por favor intente más tarde.');
                      }
                  });
        return qUpdate.promise;
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
    self.deleteRequest = function (request) {
        var qDelReq = $q.defer();
        $http.post('index.php/AgentHomeController/deleteRequest',
                   JSON.stringify(request))
            .then(function (response) {
                      if (response.data.message == "success") {
                          qDelReq.resolve();
                      } else {
                          qDelReq.reject('Ha ocurrido un error en el sistema. ' +
                                         'Por favor intente más tarde');
                      }
                  });
        return qDelReq.promise;
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
     * * Filters all requests by type and assigns to the scope.
     *
     * @param requests - Requests array returned by the server.
     * @returns {{}} - Obj containing arrays of different types of loans.
     */
    function filterRequests(requests) {
        var req = {};
        req.pp = requests.filter(function (loan) {
            return loan.type == Constants.LoanTypes.PERSONAL;
        });
        req.vc = requests.filter(function (loan) {
            return loan.type == Constants.LoanTypes.CASH_VOUCHER;
        });
        return req;
    }

    /**
     * Gets the different kind of requests' title.
     *
     * @returns {*} - a string corresponding to the loan type's title.
     */
    self.getTypeTitles = function () {
        return loanTitles;
    };

    /**
     * Maps the specified (int) type to it's corresponding string type.
     *
     * @param type - loan type's code.
     * @returns {*} - string containing the corresponding mapped type.
     */
    self.mapLoanTypes = function (type) {
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
                      if (response.status == 200) {
                          qReqCreation.resolve();
                      } else {
                          qReqCreation.reject('Ha ocurrido un error al crear su solicitud. ' +
                                              'Por favor intente más tarde');
                      }
                  });
        return qReqCreation.promise;
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
